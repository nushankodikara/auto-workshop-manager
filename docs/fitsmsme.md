Profile API
Fit SMS Profile API allows you to retrieve your total remaining sms unit, used sms unit, and your profile information.
API Endpoint

Markup
https://app.fitsms.lk/api/v4/me
Parameters
Parameter	Required	Description
Authorization	Yes	When calling our API, send your api token with the authentication type set as Bearer (Example: Authorization: Bearer {api_token})
Accept	Yes	Set to application/json
View sms unit
API Endpoint

Markup
https://app.fitsms.lk/api/v4/balance
Example request
PHP
curl -X GET https://app.fitsms.lk/api/v4/balance \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
Returns
Returns a contact object if the request was successful.

JSON
{
    "status": "success",
    "data": "sms unit with all details",
}
If the request failed, an error object will be returned.

JSON
{
    "status": "error",
    "message" : "A human-readable description of the error."
}