# Install wireguard on the system
sudo apt install wireguard

# Copy the preconfigured wireguard file I made
sudo cp wg0.conf /etc/wireguard/wg0.conf

# Edit the file with the appropriate fields.
# There should only be the PrivateKey and Address you should be changing! 
# Your Private key is in the privkey directory. Just use cat to show the contents of the file.
# It should look like this:

[Interface]
PrivateKey = 123456qwerty
Address = 10.0.0.5
# Change the Address to the corresponding peer:
# Mike: 10.0.0.2 
# Warlin: 10.0.0.3 
# Raj: 10.0.0.4
DNS = 1.1.1.1

[Peer]
PublicKey = LnkCirPWRHjM0No6dqnfJsmHEnF7hlZc6Xmw5J8IrzM=
Endpoint = 23.92.21.192:51820
AllowedIPs = 0.0.0.0/0, ::/0  # Route all traffic through the VPN

# Let's practice a little bit of security and set permissions for the config file
sudo chmod 600 /etc/wireguard/wg0.conf

# Start wireguard
sudo wg-quick up wg0

# Stop wireguard
sudo wg-quick down wg0

# Show interface status
# Will only show if wireguard is up
sudo wg

