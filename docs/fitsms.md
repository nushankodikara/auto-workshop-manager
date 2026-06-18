Fit SMS SMS API allows you to send and receive SMS messages to and from any country in the world through a REST API. Each message is identified by a unique random ID so that users can always check the status of a message using the given endpoint.
API Endpoint

Markup
https://app.fitsms.lk/api/v4/sms/send
Parameters
Parameter	Required	Description
Authorization	Yes	When calling our API, send your api token with the authentication type set as Bearer (Example: Authorization: Bearer {api_token})
Accept	Yes	Set to application/json
Send outbound SMS
Fit SMS's Programmable SMS API enables you to programmatically send SMS messages from your web application. First, you need to create a new message object. Fit SMS returns the created message object with each request.

Send your first SMS message with this example request.

API Endpoint

Markup
https://app.fitsms.lk/api/v4/sms/send
Parameters
Parameter	Required	Type	Description
recipient	Yes	string	Number to send message. Use comma (,) to send multiple numbers. Ex. 31612345678,8801721970168
sender_id	Yes	string	The sender of the message. This can be a telephone number (including country code) or an alphanumeric string. In case of an alphanumeric string, the maximum length is 11 characters.
type	Yes	string	The type of the message. For text message you have to insert plain as sms type.
message	Yes	string	The body of the SMS message.
schedule_time	No	datetime	The scheduled date and time of the message in RFC3339 format (Y-m-d H:i)
dlt_template_id	No	string	The ID of your registered DLT (Distributed Ledger Technology) content template.
expiry_time	No	integer (Seconds)	The date and time after which the message is considered failed if it has not been delivered. Must be at least +60 Seconds from the current time. Input should be in Integer. (Default: 24 hours after creation, Max: 24 hours after creation). SMS sending timezone is considered as UTC +5:30 (Asia/Colombo).
Example request for Single Number
PHP
curl -X POST https://app.fitsms.lk/api/v4/sms/send \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-d '{
"recipient":"31612345678",
"sender_id":"YourName",
"type":"plain",
"message":"This is a test message",
"expiry_time":3600
}'
Example request for Multiple Numbers
PHP
curl -X POST https://app.fitsms.lk/api/v4/sms/send \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-d '{
  "recipient": "31612345678,880172145789",
  "sender_id": "YourName",
  "type": "plain",
  "message": "This is a test message",
  "schedule_time": "2021-12-20 07:00",
  "expiry_time":3600
}'
Returns
Returns a contact object if the request was successful.

JSON
{
  "status": "success",
  "data": {
    "ruid": "03bd1b3d590f40819aa83a49c1ca1a41",
    "total_receivers": 3,
    "to": "94771234567,94776543210,94770000000",
    "message": "Hello, your OTP is 123456",
    "sms_type": "plain",
    "status": "pending",
    "from": "MyBrand",
    "send_by": "api",
    "created_at": "2025-09-01T09:45:30Z",
    "expired_at": "2025-09-01T09:46:30+05:30"
  }
}
If the request failed, an error object will be returned.

JSON
{
    "status": "error",
    "message" : "A human-readable description of the error."
}
Field	Type	Description	Example
status	string	The API response status. Indicates whether the request was processed successfully or failed. Possible values:
success
error
"success"
data.ruid	string(32)	Unique identifier (hash) for the SMS request. Used for tracking.	"03bd1b3d590f40819aa83a49c1ca1a41"
data.total_receivers	integer	Total number of valid recipients included in the request.	3
data.to	string	Comma-separated list of recipient phone numbers.	"94771234567,94776543210"
data.message	string	The actual text message content being sent.	"Hello, your OTP is 123456"
data.sms_type	string	Type of SMS being sent. Common values: plain, unicode.	"plain"
data.status	string	Current processing status of the request. Possible values:
pending – request accepted, waiting to be processed
delivered – SMS successfully delivered
failed – SMS could not be delivered
"pending"
data.from	string	The sender ID (alphanumeric or phone number) that the recipient will see.	"MyBrand"
data.send_by	string	Identifies the source of the request. Can be api, or other sources.	"api"
data.created_at	datetime	The date and time when the request was created (ISO 8601 format recommended).	"2025-09-01T09:45:30Z"
data.expired_at	string	The timestamp (ISO 8601) when the message will expire if it has not been delivered, after which it will be considered failed (Default: 1 hour after creation). Reasons for possible delay include:
Recipient’s phone is switched off or unreachable
Network issues on the delivery side
Temporary carrier delays
System retries or processing queue delays
Send Campaign Using Contact list
Fit SMS's Programmable SMS API enables you to programmatically send Campaigns from your web application. First, you need to create a new message object. Fit SMS returns the created message object with each request.

Send your first Campaign Using Contact List with this example request.

API Endpoint

Markup
https://app.fitsms.lk/api/v4/sms/campaign
Parameters
Parameter	Required	Type	Description
contact_list_id	Yes	string	Contact list to send message. Use comma (,) to send multiple contact lists. Ex. 6415907d0d7a6,6415907d0d37a
sender_id	Yes	string	The sender of the message. This can be a telephone number (including country code) or an alphanumeric string. In case of an alphanumeric string, the maximum length is 11 characters.
type	Yes	string	The type of the message. For text message you have to insert plain as sms type.
message	Yes	string	The body of the SMS message.
schedule_time	No	datetime	The scheduled date and time of the message in RFC3339 format (Y-m-d H:i)
dlt_template_id	No	string	The ID of your registered DLT (Distributed Ledger Technology) content template.
Example request for Single Contact List
PHP
curl -X POST https://app.fitsms.lk/api/v4/sms/campaign \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-d '{
"contact_list_id":"6415907d0d37a",
"sender_id":"YourName",
"type":"plain",
"message":"This is a test message"
}'
Example request for Multiple Contact Lists
PHP
curl -X POST https://app.fitsms.lk/api/v4/sms/campaign \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-d '{
"contact_list_id":"6415907d0d37a,6415907d0d7a6",
"sender_id":"YourName",
"type":"plain",
"message":"This is a test message",
"schedule_time=2021-12-20 07:00"
}'
Returns
Returns a contact object if the request was successful.

JSON
{
    "status": "success",
    "data": {
        "uid": "68b265728c72f",
        "campaign_name": "TEST",
        "status": "done"
    },
}
If the request failed, an error object will be returned.

JSON
{
    "status": "error",
    "message" : "A human-readable description of the error."
}
View an SMS
You can use Fit SMS's SMS API to retrieve information of an existing inbound or outbound SMS message.

You only need to supply the unique message id that was returned upon creation or receiving.

API Endpoint

Markup
https://app.fitsms.lk/api/v4/sms/{ruid}
Parameters
Parameter	Required	Type	Description
ruid	Yes	string(32)	A unique random uid which is created on the Fit SMS platform and is returned upon creation of the object.
recipient	Yes	string	Number to retrieve message information. Ex. 31612345678
Example request
PHP
curl -X GET https://app.fitsms.lk/api/v4/sms/03bd1b3d590f40819aa83a49c1ca1a41 \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-d '{
"recipient":"94770000000",
}'
Returns
Returns a contact object if the request was successful.

JSON
{
    "status": "success",
    "data": {
        "uid": "68b401b9cd208",
        "to": "94770000000",
        "from": "MyBrand",
        "message": "Test",
        "sms_type": "plain",
        "direction": "api",
        "status": "Delivered",
        "sms_count": 1,
        "cost": "1.25",
        "sent_at": "2025-08-31 13:33:05"
    }
}
If the request failed, an error object will be returned.

JSON
{
    "status": "error",
    "message" : "A human-readable description of the error."
}
Webhook Response
When requesting a sender ID, users must configure a Webhook URL to receive the response.

Send a POST request with the following parameters:

Example Response
JSON
{
    "status": "success",
    "data": {
        "to": "94770000000",
        "from": "MyBrand",
        "message": "Test",
        "sms_type": "plain",
        "sms_count": 1,
        "cost": 1.25,
        "send_by": "api",
        "ruid": "fe424939fc3c4b6dbcc876994517d712",
        "received_at": "2025-09-05T23:24:22+05:30",
        "expired_at": "2025-09-05T23:25:22+05:30"
    }
}
Parameters
Parameter	Type	Description
status	string	The delivery status of the message. Possible values: success, failed.
data.ruid	string(32)	A unique identifier generated by the Fit SMS platform for each message. Returned when the object is created.
data.to	string	The recipient's phone number. Example: 31612345678.
data.from	string	The sender ID or phone number from which the message is sent.
data.message	string	The actual text content of the SMS message.
data.cost	string	The cost of sending this message, in the account’s currency.
data.sms_count	integer	The number of SMS segments used for this message (1 for messages ≤160 characters, more if longer).
data.send_by	string	The source of the request. Typically api, but may include other sources.
data.sms_type	string	The type of SMS sent. Common values: plain (regular text), unicode (supports special characters/emojis).
data.received_at	string | null	The timestamp (ISO 8601) when the message was successfully received. If the message has not yet been received, this value will be null.
data.expired_at	string	The timestamp (ISO 8601) when the message will expire if it has not been delivered, after which it will be considered failed (Default: 1 hour after creation). Reasons for possible delay include:
Recipient’s phone is switched off or unreachable
Network issues on the delivery side
Temporary carrier delays
System retries or processing queue delays
View all messages
API Endpoint

Markup
https://app.fitsms.lk/api/v4/sms/
Example request
PHP
curl -X GET https://app.fitsms.lk/api/v4/sms \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
Returns
Returns a contact object if the request was successful.

JSON
{
    "status": "success",
    "data": "sms reports with pagination",
}
If the request failed, an error object will be returned.

JSON
{
    "status": "error",
    "message" : "A human-readable description of the error."
}
📅 View Messages via DateTime, SMS Type & Timezone
API Endpoint

Markup
GET https://app.fitsms.lk/api/v4/sms?start_date={YYYY-MM-DD HH:MM:SS}&end_date={YYYY-MM-DD HH:MM:SS}&sms_type={plain|unicode|voice|mms|whatsapp|otp|viber}&direction={outgoing|incoming|api}&timezone={e.g. Asia/Hong_Kong}
Parameters
Parameter	Required	Type	Description
start_date	Yes	string	Start datetime to filter messages. Format: YYYY-MM-DD HH:MM:SS. Must be in the timezone provided (defaults to UTC).
end_date	Yes	string	End datetime to filter messages. Format: YYYY-MM-DD HH:MM:SS. Must be in the timezone provided (defaults to UTC).
timezone	No	string	Optional. IANA timezone (e.g., Asia/Hong_Kong, UTC, America/New_York). Used to interpret start_date and end_date.
sms_type	No	string	Optional. Filter by SMS type: plain, unicode, voice, mms, whatsapp, otp, viber.
direction	No	string	Optional. Filter by SMS direction: outgoing, incoming, api.
🧪 Example Request
PHP
curl -X GET "https://app.fitsms.lk/api/v4/sms?start_date=2025-05-01 08:00:00&end_date=2025-05-22 18:00:00&sms_type=plain&direction=outgoing&timezone=Asia/Hong_Kong" \
  -H "Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
✅ Success Response
JSON
{
    "status": "success",
    "message": "SMS data fetched successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "uid": "683831eda796e",
                "to": "8801721970168",
                "from": "MyBrand",
                "message": "test message",
                "sms_type": "plain",
                "direction": "api",
                "status": "Delivered",
                "sms_count": 1,
                "cost": "1",
                "sent_at": "2025-05-29 16:07:41"
            }
        ],
        "first_page_url": "https://app.fitsms.lk/api/v4/sms?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "https://app.fitsms.lk/api/v4/sms?page=1",
        "links": [
            {
                "url": null,
                "label": "« Previous",
                "active": false
            },
            {
                "url": "https://app.fitsms.lk/api/v4/sms?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": null,
                "label": "Next »",
                "active": false
            }
        ],
        "next_page_url": null,
        "path": "https://app.fitsms.lk/api/v4/sms",
        "per_page": 25,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
❌ Error Response
JSON
{
  "status": "error",
  "message": "Invalid datetime format. Use Y-m-d H:i:s"
}
View Campaign
You can use Fit SMS's Campaign API to retrieve information of an existing Campaigns.

You only need to supply the unique campaign id that was returned upon creation or receiving.

API Endpoint

Markup
https://app.fitsms.lk/api/v4/campaign/{uid}/view
Parameters
Parameter	Required	Type	Description
uid	Yes	string	A unique random uid which is created on the Fit SMS platform and is returned upon creation of the object.
Example request
PHP
curl -X GET https://app.fitsms.lk/api/v4/campaign/68b265728c72f/view \
-H 'Authorization: Bearer 430|AFON0n9enq7gCqmYIwnGYiOrie7H6IQnBX6E5lC7d65ff251' \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
Returns
Returns a contact object if the request was successful.

JSON
{
    "status": "success",
    "data": {
        "id": "68b265728c72f",
        "name": "TEST",
        "message": "TEST",
        "status": "done",
        "type": "plain",
        "created_at": "30th Aug 25, 8:14 AM",
        "start_at": "30th Aug 25, 8:18 AM",
        "delivery_at": "30th Aug 25, 8:18 AM",
        "stats": {
            "enroute_count": 0,
            "delivered_count": 1,
            "expired_count": 0,
            "undelivered_count": 0,
            "rejected_count": 0,
            "accepted_count": 0,
            "skipped_count": 0,
            "failed_count": 0
        }
    }
}
If the request failed, an error object will be returned.

JSON
{
    "status": "error",
    "message" : "A human-readable description of the error."
}