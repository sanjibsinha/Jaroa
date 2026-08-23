#!/usr/bin/env bash

BASE_URL="https://fullstack-app.ddev.site/wp-json/fullstack/v1/categories"

PASS=0
FAIL=0

run_status_test() {
	local name="$1"
	local expected="$2"
	local url="$3"

	response=$(curl -s -w "\n%{http_code}" "$url")

	body=$(echo "$response" | sed '$d')
	status=$(echo "$response" | tail -n 1)

	if [[ "$status" == "$expected" ]]; then
		echo "PASS: $name [$status]"
		((PASS++))
	else
		echo "FAIL: $name [$status, expected $expected]"
		echo "Response:"
		echo "$body"
		((FAIL++))
	fi
}

run_json_test() {
	local name="$1"
	local url="$2"
	local jq_filter="$3"

	body=$(curl -s "$url")

	if echo "$body" | jq -e "$jq_filter" > /dev/null 2>&1; then
		echo "PASS: $name"
		((PASS++))
	else
		echo "FAIL: $name"
		echo "Response:"
		echo "$body"
		((FAIL++))
	fi
}

echo "Testing Full Stack App Categories API"
echo "======================================"

# --------------------------------------------------
# HTTP status tests
# --------------------------------------------------

run_status_test \
	"Basic categories request" \
	"200" \
	"$BASE_URL"

run_status_test \
	"Include empty categories" \
	"200" \
	"$BASE_URL?hide_empty=false"

run_status_test \
	"Hide empty categories" \
	"200" \
	"$BASE_URL?hide_empty=true"

# --------------------------------------------------
# Find an existing category dynamically.
# --------------------------------------------------

category_response=$(curl -s "$BASE_URL")

category_id=$(
	echo "$category_response" \
		| jq -r '.data[0].id // empty'
)

if [[ -n "$category_id" ]]; then

	run_status_test \
		"Single category" \
		"200" \
		"$BASE_URL/$category_id"

else

	echo "FAIL: Could not find an existing category ID"
	((FAIL++))

fi

# --------------------------------------------------
# Error handling
# --------------------------------------------------

run_status_test \
	"Non-existent category" \
	"404" \
	"$BASE_URL/99999"

run_status_test \
	"Invalid category ID" \
	"404" \
	"$BASE_URL/abc"

# --------------------------------------------------
# JSON collection structure
# --------------------------------------------------

run_json_test \
	"Categories response has data" \
	"$BASE_URL" \
	'.data | type == "array"'

run_json_test \
	"Categories response has meta" \
	"$BASE_URL" \
	'.meta | type == "object"'

run_json_test \
	"Meta contains total" \
	"$BASE_URL" \
	'.meta.total | type == "number"'

# --------------------------------------------------
# Category object structure
# --------------------------------------------------

if [[ -n "$category_id" ]]; then

	CATEGORY_URL="$BASE_URL/$category_id"

	run_json_test \
		"Category has required fields" \
		"$CATEGORY_URL" \
		'.data | has("id", "name", "slug", "count")'

	run_json_test \
		"Category ID is numeric" \
		"$CATEGORY_URL" \
		'.data.id | type == "number"'

	run_json_test \
		"Category name is string" \
		"$CATEGORY_URL" \
		'.data.name | type == "string"'

	run_json_test \
		"Category slug is string" \
		"$CATEGORY_URL" \
		'.data.slug | type == "string"'

	run_json_test \
		"Category count is numeric" \
		"$CATEGORY_URL" \
		'.data.count | type == "number"'

fi

# --------------------------------------------------
# Validate collection items.
# --------------------------------------------------

run_json_test \
	"Every category has required fields" \
	"$BASE_URL" \
	'all(.data[]; has("id", "name", "slug", "count"))'

run_json_test \
	"Every category has numeric ID" \
	"$BASE_URL" \
	'all(.data[]; .id | type == "number")'

run_json_test \
	"Every category has string name" \
	"$BASE_URL" \
	'all(.data[]; .name | type == "string")'

run_json_test \
	"Every category has string slug" \
	"$BASE_URL" \
	'all(.data[]; .slug | type == "string")'

run_json_test \
	"Every category has numeric count" \
	"$BASE_URL" \
	'all(.data[]; .count | type == "number")'

echo
echo "======================================"
echo "Passed: $PASS"
echo "Failed: $FAIL"

# --------------------------------------------------
# Error JSON contract
# --------------------------------------------------

run_json_test \
	"Missing category has error object" \
	"$BASE_URL/99999" \
	'.error | type == "object"'

run_json_test \
	"Missing category has error code" \
	"$BASE_URL/99999" \
	'.error.code == "fullstack_category_not_found"'

run_json_test \
	"Missing category has error message" \
	"$BASE_URL/99999" \
	'.error.message == "Category not found."'

run_json_test \
	"Missing category has HTTP status in JSON" \
	"$BASE_URL/99999" \
	'.error.status == 404'

if [[ "$FAIL" -gt 0 ]]; then
	exit 1
fi