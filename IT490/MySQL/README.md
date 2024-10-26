# MySQL Database Server
Author: Judrianne Mahigne

NJIT Email: jm2489@njit.edu

This is the MySQL portion for the fall 2024 IT490 - Systems Integration midterm project.

## Table of Contents

- [Introduction](#introduction)
- [dbClient.php](#dbclientinc)
- [dbServer.php](#dbserverphp)
- [Usage](#usage)

## Introduction

This contains the setup of the MySQL database. It includes php scripts for the database server to handle when RabbitMQ sends over a json message with a query field.

## dbClient.inc

This file contains the necessary MySQL credentials to connect to the database as well as any CRUD related statements. Without this file, the main server script will fail. As well as any incorrect credentials or connection details.

## dbServer.php

This file contains the main server script. It listens for incoming messages from RabbitMQ and processes them accordingly. It uses socket based connections over TCP up to 3 simultaneous connections at a time. This can be increased by modifying the `socket_listen()` parameter in one of the if statements.

By default, the the script accepts any and all connections. You can change this by changing the `$host` and `$port` variables respectfully. I advise that you set proper firewall rules to the database server to further increase network security.

In all responses that the script returns will be encoded JSON format. You can change this how you see fit but I prefer this format as it's easier for me to read and debug.

One important thing to know is that the connections, after a successful query, must be closed gracefully as I was running into connection issues when multiple requests were sent. This is achieved by adding a `socket_shutdown()` and a `socket_close()` to the connection in that order. You can also add the `socket_shutdown()` to the socket as well but it seems redundant if the server is undergoing a reboot or shutdown anyway. 

## Debugging

If you're having trouble with the script, I recommend checking the RabbitMQ logs for any errors **FIRST**. But if you know the issue is coming from the database you must check the following:
+ Check to see if the MySQL server is running. You can check this by running `sudo systemctl status mysql-server`
+ Check to see if the MySQL credentials are correct. You can check this by running `mysql -
u [username] -p[password]`
+ Check to see if the database exists. You can check this by running `mysql -u [
    username] -p[password] -e "SHOW DATABASES;"`
+ Check to see if the service is running: `sudo systemctl status dbServer.service`
+ Check to see if the database is even on the same network as the rabbitmq server and vice-versa.
+ Check to see if the firewall rules are configured correctly.

### Using `telnet` to test the connection
From the same machine you could run the following command to see if the connection is open: `telnet localhost 8888` change the port accordingly here as the default is **8888**.

If the connection is open and successful, you should see a response similar to this:
```
❯ telnet 127.0.0.1 8888
Trying 127.0.0.1...
Connected to 127.0.0.1.
Escape character is '^]'.
```
That's right, the connection to the database can be made via telnet. Yes, I am aware that telnet for transmitting sensitive data is a security risk. But since the network environment that the servers will be operating on are VPN connections. All the data exchanged between servers, by default, are tunneled through the VPN and have a fallback if they lose connection or is depreciated. The fallback being they lose connection entirely and must be handled locally on the host machine. Other security implementations I could incorporate is basic netowork segmentation using VLANS and setting up proper VLAN rules as well as port security if all servers are connected in a LAN environment.

## Usage

To use the database, you can run queries using simple MySQL queries. They must be formatted in JSON in order for the server to read the data properly.

For example the following query: 
```json
{"query": "SELECT * FROM users WHERE username = 'steve'"}
```
Will return the following response:
```json
{"success":"success","data":[{"id":1,"username":"steve","password":"<HASHED_PASSWORD>","last_login":123456}]}
```
The server will return all the rows from the `users` table where the `username` column is equal to that of `'steve'` in encoded JSON format.

Any **CRUD** related statements work similarly.

### Running as a service
To run the script as a service, you can create a systemd service file in the `/etc/system
d/system/` directory. Here's an example service file:

> dbServer.service
```ini
[Unit]
Description=PHP Database Socket Server
After=network.target mysql.service
Wants=mysql.service

[Service]
ExecStart=/usr/bin/php /path/to/your/dbServer.php
Restart=always
RestartSec=5
User=www-data
Group=www-data
StandardOutput=journal
StandardError=journal
WorkingDirectory=/path/to/your/directory/

[Install]
WantedBy=multi-user.target
```

Then, you can enable and start the service with the following commands in the following order:

```bash
sudo systemctl daemon-reload
sudo systemctl enable dbServer.service
sudo systemctl start dbServer.service
```
You can also use `systemctl status dbServer.service` to check the status of the service.

## Schema
The table schema for the `logindb` database is as follows:
#### Table: users
```sql
+------------+--------------+------+-----+---------+----------------+
| Field      | Type         | Null | Key | Default | Extra          |
+------------+--------------+------+-----+---------+----------------+
| id         | int(11)      | NO   | PRI | NULL    | auto_increment |
| username   | varchar(255) | NO   | UNI | NULL    |                |
| password   | varchar(255) | NO   |     | NULL    |                |
| last_login | bigint(20)   | YES  |     | NULL    |                |
+------------+--------------+------+-----+---------+----------------+
```
#### Table: sessions
```sql
+---------------+--------------+------+-----+------------------+----------------+
| Field         | Type         | Null | Key | Default          | Extra          |
+---------------+--------------+------+-----+------------------+----------------+
| id            | int(11)      | NO   | PRI | NULL             | auto_increment |
| username      | varchar(255) | NO   |     | NULL             |                |
| session_token | varchar(255) | NO   | UNI | NULL             |                |
| created_at    | bigint(20)   | YES  |     | unix_timestamp() |                |
| expire_date   | bigint(20)   | NO   |     | NULL             |                |
+---------------+--------------+------+-----+------------------+----------------+
```