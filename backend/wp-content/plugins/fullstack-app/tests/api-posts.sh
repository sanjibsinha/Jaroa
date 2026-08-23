#!/usr/bin/env bash

BASE_URL="https://fullstack-app.ddev.site/wp-json/fullstack/v1/posts"

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

echo "Testing Full Stack App Posts API"
echo "================================"

# --------------------------------------------------
# HTTP status tests
# --------------------------------------------------

run_status_test \
	"Basic posts request" \
	"200" \
	"$BASE_URL"

run_status_test \
	"Pagination" \
	"200" \
	"$BASE_URL?page=1&per_page=2"

run_status_test \
	"Search" \
	"200" \
	"$BASE_URL?search=wordpress"

run_status_test \
	"Sorting" \
	"200" \
	"$BASE_URL?orderby=title&order=asc"

run_status_test \
	"Combined query" \
	"200" \
	"$BASE_URL?search=wordpress&page=1&per_page=2&orderby=title&order=asc"

run_status_test \
	"Invalid per_page" \
	"400" \
	"$BASE_URL?per_page=100"

run_status_test \
	"Invalid order" \
	"400" \
	"$BASE_URL?order=random"

run_status_test \
	"Invalid orderby" \
	"400" \
	"$BASE_URL?orderby=random"

run_status_test \
	"Invalid page" \
	"400" \
	"$BASE_URL?page=0"

# --------------------------------------------------
# JSON structure tests
# --------------------------------------------------

run_json_test \
	"Posts response has data" \
	"$BASE_URL" \
	'.data | type == "array"'

run_json_test \
	"Posts response has meta" \
	"$BASE_URL" \
	'.meta | type == "object"'

run_json_test \
	"Meta contains page" \
	"$BASE_URL" \
	'.meta.page | type == "number"'

run_json_test \
	"Meta contains per_page" \
	"$BASE_URL" \
	'.meta.per_page | type == "number"'

run_json_test \
	"Meta contains total" \
	"$BASE_URL" \
	'.meta.total | type == "number"'

run_json_test \
	"Meta contains total_pages" \
	"$BASE_URL" \
	'.meta.total_pages | type == "number"'

# --------------------------------------------------
# Post object structure
# --------------------------------------------------

run_json_test \
	"Post has required fields" \
	"$BASE_URL?per_page=1" \
	'.data[0] | has(
		"id",
		"title",
		"slug",
		"excerpt",
		"content",
		"date",
		"author",
		"categories",
		"featured_image",
		"link"
	)'

run_json_test \
	"Post ID is numeric" \
	"$BASE_URL?per_page=1" \
	'.data[0].id | type == "number"'

run_json_test \
	"Post title is string" \
	"$BASE_URL?per_page=1" \
	'.data[0].title | type == "string"'

run_json_test \
	"Post slug is string" \
	"$BASE_URL?per_page=1" \
	'.data[0].slug | type == "string"'

run_json_test \
	"Post author is object" \
	"$BASE_URL?per_page=1" \
	'.data[0].author | type == "object"'

run_json_test \
	"Author has id and name" \
	"$BASE_URL?per_page=1" \
	'.data[0].author | has("id", "name")'

run_json_test \
	"Post categories is array" \
	"$BASE_URL?per_page=1" \
	'.data[0].categories | type == "array"'

run_json_test \
	"Featured image is null or object" \
	"$BASE_URL?per_page=1" \
	'.data[0].featured_image == null or (.data[0].featured_image | type == "object")'

run_json_test \
	"Post link is string" \
	"$BASE_URL?per_page=1" \
	'.data[0].link | type == "string"'

echo
echo "================================"
echo "Passed: $PASS"
echo "Failed: $FAIL"

# --------------------------------------------------
# Error JSON contract
# --------------------------------------------------

run_json_test \
	"Missing post has error object" \
	"$BASE_URL/99999" \
	'.error | type == "object"'

run_json_test \
	"Missing post has error code" \
	"$BASE_URL/99999" \
	'.error.code == "fullstack_post_not_found"'

run_json_test \
	"Missing post has error message" \
	"$BASE_URL/99999" \
	'.error.message == "Post not found."'

run_json_test \
	"Missing post has HTTP status in JSON" \
	"$BASE_URL/99999" \
	'.error.status == 404'

	# --------------------------------------------------
# Validation error JSON contract
# --------------------------------------------------

run_json_test \
	"Invalid per_page has error object" \
	"$BASE_URL?per_page=100" \
	'.error | type == "object"'

run_json_test \
	"Invalid per_page has error code" \
	"$BASE_URL?per_page=100" \
	'.error.code == "rest_invalid_param"'

run_json_test \
	"Invalid per_page has error message" \
	"$BASE_URL?per_page=100" \
	'.error.message | type == "string"'

run_json_test \
	"Invalid per_page has status 400" \
	"$BASE_URL?per_page=100" \
	'.error.status == 400'

run_json_test \
	"Invalid order has status 400" \
	"$BASE_URL?order=random" \
	'.error.status == 400'
	
run_json_test \
	"Invalid category ID has error object" \
	"$BASE_URL/0" \
	'.error | type == "object"'

if [[ "$FAIL" -gt 0 ]]; then
	exit 1
fi

