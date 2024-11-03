import requests
import json

# Kraken API endpoint for asset pairs
url = 'https://api.kraken.com/0/public/AssetPairs'

response = requests.get(url)
pairs = response.json()

# Filter pairs containing BTC (XBT) and ETH
btc_eth_pairs = {key: value for key, value in pairs['result'].items() if 'XBT' in key and 'ETH' in key}

print(json.dumps(btc_eth_pairs, indent=4))