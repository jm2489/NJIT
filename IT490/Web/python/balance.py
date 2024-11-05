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
load_dotenv("k_API.env")

# Kraken API endpoint for balance inquiry
url = "https://api.kraken.com/0/private/Balance"

# Retrieve the API key and secret
api_key = os.getenv('KRAKEN_API_KEY')
api_secret = os.getenv('KRAKEN_API_SECRET')

# Check if the keys are loaded
if not api_key or not api_secret:
    print("Error: API key or secret not loaded. Check k_API.env file.")
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

# Send the POST request
response = requests.post(url, headers=headers, data=postdata)

# Print response
print(json.dumps(response.json(), indent=4))
