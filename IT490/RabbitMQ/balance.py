import os
import requests
import time
import hashlib
import hmac
import base64
import json
from dotenv import load_dotenv
import urllib.parse

# Load environment variables from k_API.env file
env_path = os.path.join(os.path.expanduser("~"), "RabbitMQ", "k_API.env")
load_dotenv(env_path)

# Kraken API endpoint for balance inquiry
url = "https://api.kraken.com/0/private/Balance"

# Retrieve the API key and secret
api_key = os.getenv('KRAKEN_API_KEY')
api_secret = os.getenv('KRAKEN_API_SECRET')

# Exit early if keys are missing
if not api_key or not api_secret:
    print(json.dumps({"error": "API key or secret not loaded. Check k_API.env file."}))
    exit()

# Generate a nonce automatically
nonce = str(int(time.time() * 1000000))

# Data for the request
data = {
    'nonce': nonce
}

# Encode data for API-Sign
postdata = urllib.parse.urlencode(data)
encoded = (nonce + postdata).encode()
message = b"/0/private/Balance" + hashlib.sha256(encoded).digest()
signature = hmac.new(base64.b64decode(api_secret), message, hashlib.sha512)
api_sign = base64.b64encode(signature.digest()).decode()

# Headers for the request
headers = {
    'API-Key': api_key,
    'API-Sign': api_sign
}

# Send the POST request and handle errors
try:
    response = requests.post(url, headers=headers, data=postdata)
    response.raise_for_status()  # Check for HTTP errors

    # Parse the response JSON
    response_data = response.json()

    # Print only the result if there are no API errors
    if response_data.get("error"):
        print(json.dumps({"error": response_data["error"]}))
    else:
        print(json.dumps(response_data["result"]))  # Print only the result

except requests.exceptions.RequestException as e:
    # Print any request-related errors as JSON
    print(json.dumps({"error": str(e)}))
