#!/usr/bin/env bash
set -euo pipefail

BASE="https://jaroa-engine.ddev.site/api/v1"
STAMP="$(date +%s%N)"
PASS="JaroaC2!${STAMP}"
NEWPASS="JaroaNewC2!${STAMP}"

EMAIL_A="c2a_${STAMP}@example.test"
EMAIL_B="c2b_${STAMP}@example.test"
EMAIL_ADMIN="c2admin_${STAMP}@example.test"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

fail() {
    echo
    echo "❌ FAIL: $1"
    echo
    echo "Response:"
    cat "$TMP/body" 2>/dev/null || true
    exit 1
}

request() {
    local method="$1"
    local url="$2"
    local body="${3:-}"
    local token="${4:-}"

    if [[ -n "$token" ]]; then
        curl -ksS -X "$method" "$url" \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json" \
            ${body:+--data "$body"} \
            -o "$TMP/body" \
            -w '%{http_code}' > "$TMP/status"
    else
        curl -ksS -X "$method" "$url" \
            -H "Content-Type: application/json" \
            ${body:+--data "$body"} \
            -o "$TMP/body" \
            -w '%{http_code}' > "$TMP/status"
    fi

    STATUS="$(cat "$TMP/status")"
}

get_json() {
    local expression="$1"

    python3 -c "
import json
with open('$TMP/body') as f:
    data = json.load(f)
print($expression)
"
}

assert_status() {
    local expected="$1"
    local message="$2"

    [[ "$STATUS" == "$expected" ]] || fail \
        "$message (expected HTTP $expected, got $STATUS)"
}

echo "========================================"
echo " JAROA CAMPAIGN 2 USER MANAGEMENT HAMMER"
echo "========================================"
echo

echo "1. Registering User A..."
request POST "$BASE/auth/register" \
    "{\"name\":\"Campaign Two User A\",\"email\":\"$EMAIL_A\",\"password\":\"$PASS\"}"
assert_status 201 "User A registration failed"

echo "2. Registering User B..."
request POST "$BASE/auth/register" \
    "{\"name\":\"Campaign Two User B\",\"email\":\"$EMAIL_B\",\"password\":\"$PASS\"}"
assert_status 201 "User B registration failed"

echo "3. Registering Admin..."
request POST "$BASE/auth/register" \
    "{\"name\":\"Campaign Two Admin\",\"email\":\"$EMAIL_ADMIN\",\"password\":\"$PASS\"}"
assert_status 201 "Admin registration failed"

echo "4. Logging in User A..."
request POST "$BASE/auth/login" \
    "{\"email\":\"$EMAIL_A\",\"password\":\"$PASS\"}"
assert_status 200 "User A login failed"

TOKEN_A="$(get_json "data['data']['token']")"
ID_A="$(get_json "data['data']['user']['id']")"

echo "5. Logging in User B..."
request POST "$BASE/auth/login" \
    "{\"email\":\"$EMAIL_B\",\"password\":\"$PASS\"}"
assert_status 200 "User B login failed"

TOKEN_B="$(get_json "data['data']['token']")"
ID_B="$(get_json "data['data']['user']['id']")"

echo "6. Logging in Admin..."
request POST "$BASE/auth/login" \
    "{\"email\":\"$EMAIL_ADMIN\",\"password\":\"$PASS\"}"
assert_status 200 "Admin login failed"

TOKEN_ADMIN="$(get_json "data['data']['token']")"
ID_ADMIN="$(get_json "data['data']['user']['id']")"

echo "   Created users: A=$ID_A B=$ID_B ADMIN=$ID_ADMIN"

echo "7. Promoting Admin..."
ddev exec mysql -udb -pdb db \
    -e "UPDATE users SET role='admin' WHERE id=${ID_ADMIN};" >/dev/null

echo "8. Owner GET own profile..."
request GET "$BASE/users/$ID_A" "" "$TOKEN_A"
assert_status 200 "Owner cannot access own profile"

echo "9. User A GET User B must be forbidden..."
request GET "$BASE/users/$ID_B" "" "$TOKEN_A"
assert_status 403 "Cross-account GET was not forbidden"

echo "10. Admin GET User B..."
request GET "$BASE/users/$ID_B" "" "$TOKEN_ADMIN"
assert_status 200 "Admin cannot access another user"

echo "11. Owner PUT own profile..."
request PUT "$BASE/users/$ID_A" \
    "{\"name\":\"Campaign Two Updated\",\"email\":\"$EMAIL_A\"}" \
    "$TOKEN_A"
assert_status 200 "Owner profile update failed"

echo "12. User A PUT User B must be forbidden..."
request PUT "$BASE/users/$ID_B" \
    "{\"name\":\"HACKED\",\"email\":\"$EMAIL_B\"}" \
    "$TOKEN_A"
assert_status 403 "Cross-account PUT was not forbidden"

echo "13. Duplicate email must return 422..."
request PUT "$BASE/users/$ID_A" \
    "{\"name\":\"Campaign Two Updated\",\"email\":\"$EMAIL_B\"}" \
    "$TOKEN_A"
assert_status 422 "Duplicate email was not rejected"

echo "14. Admin PUT User B..."
request PUT "$BASE/users/$ID_B" \
    "{\"name\":\"Admin Updated User B\",\"email\":\"$EMAIL_B\"}" \
    "$TOKEN_ADMIN"
assert_status 200 "Admin profile update failed"

echo "15. Wrong current password must return 422..."
request POST "$BASE/users/$ID_A/password" \
    "{\"current_password\":\"WRONG\",\"new_password\":\"$NEWPASS\"}" \
    "$TOKEN_A"
assert_status 422 "Wrong current password was not rejected"

echo "16. Short new password must return 422..."
request POST "$BASE/users/$ID_A/password" \
    "{\"current_password\":\"$PASS\",\"new_password\":\"short\"}" \
    "$TOKEN_A"
assert_status 422 "Short password was not rejected"

echo "17. Correct password change..."
request POST "$BASE/users/$ID_A/password" \
    "{\"current_password\":\"$PASS\",\"new_password\":\"$NEWPASS\"}" \
    "$TOKEN_A"
assert_status 200 "Password change failed"

echo "18. Old token must be revoked..."
request GET "$BASE/auth/me" "" "$TOKEN_A"
assert_status 401 "Old token remained valid after password change"

echo "19. Old password must fail..."
request POST "$BASE/auth/login" \
    "{\"email\":\"$EMAIL_A\",\"password\":\"$PASS\"}"
assert_status 401 "Old password still works"

echo "20. New password must work..."
request POST "$BASE/auth/login" \
    "{\"email\":\"$EMAIL_A\",\"password\":\"$NEWPASS\"}"
assert_status 200 "New password does not work"

TOKEN_A_NEW="$(get_json "data['data']['token']")"

echo "21. Admin self-delete must be forbidden..."
request DELETE "$BASE/users/$ID_ADMIN" "" "$TOKEN_ADMIN"
assert_status 403 "Admin self-delete was not blocked"

echo "22. Owner deletes own account..."
request DELETE "$BASE/users/$ID_A" "" "$TOKEN_A_NEW"
assert_status 200 "Owner could not delete own account"

echo "23. Deleted user's token must fail..."
request GET "$BASE/auth/me" "" "$TOKEN_A_NEW"
assert_status 401 "Deleted user's token remained valid"

echo "24. Admin deletes another user..."
request DELETE "$BASE/users/$ID_B" "" "$TOKEN_ADMIN"
assert_status 200 "Admin could not delete another user"

echo "25. Deleted user must return 404..."
request GET "$BASE/users/$ID_B" "" "$TOKEN_ADMIN"
assert_status 404 "Deleted user did not return 404"

echo "26. Missing user must return 404..."
request GET "$BASE/users/999999999" "" "$TOKEN_ADMIN"
assert_status 404 "Missing user did not return 404"

echo "27. Unauthenticated access must return 401..."
request GET "$BASE/users/$ID_ADMIN"
assert_status 401 "Unauthenticated access was not blocked"

echo "28. Cleaning test admin..."
ddev exec mysql -udb -pdb db \
    -e "DELETE FROM users WHERE id=${ID_ADMIN};" >/dev/null

echo "29. Authentication regression..."
./tests/16-authentication-hammer-test.sh

echo "30. Authorization regression..."
./tests/17-authorization-hammer-test.sh

echo
echo "========================================"
echo " JAROA CAMPAIGN 2 HAMMER PASSED"
echo "========================================"
echo
echo "User Management subsystem: COMPLETE"
echo
echo "Campaign 2 is officially COMPLETE. 🔨"
