I don't know what to say here. This is a very basic form of html and php using POST request method.
You can add a css.style if you want. This is assuming that you configured your apache server properly.

# REQUIREMENTS # 
Files that need to be in the same directory when login.php is called:
1. path.inc
2. get_host_info.inc
3. rabbitMQLib.inc
4. testRabbitMQ.ini // This needs to also be properly configured to interact with the RabbitMQ server.

Things to check if it's not working:
1. Check to see if your apache2 is running
2. Check to see if php is installed on your machine
    - php -v
3. Is php-amqp installed?
    - sudo apt install php-amqp
4. Is the testRAbbitMQ.ini file configured correctly?


