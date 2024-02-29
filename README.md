# Telnet PHP BBS Server

This repository contains a PHP Telnet Bulletin Board System (BBS) server implementation written in PHP. 
The server allows multiple clients to connect over Telnet, register, and log in to the BBS. 
Registered users can execute commands such as help and exit.

## Usage

### 1. Download the Script

Ensure you have downloaded the PHP script containing the Telnet BBS server code from this repository.

### 2. Run the Script

Open a terminal or command prompt, navigate to the directory where the script is located, and run the script using the PHP interpreter:

```bash
php telnet_bbs_server.php
```

### 3. Connect to the Server
Once the server is running, clients can connect to it using Telnet. They can do this by opening a terminal or command prompt on their machine and entering the following command:

```bash
telnet server_ip_address port_number
```
Replace server_ip_address with the IP address where your Telnet BBS server is running, and port_number with the port number specified in your script (default script is 2324 for Telnet).

### 4. Interact with the BBS
Clients can interact with the BBS by following the instructions provided in the banner message. They can register for an account by typing register and following the prompts, or log in with an existing account by typing login and entering their credentials. Once logged in, clients can execute commands such as help and exit.

### 5. Terminate the Server
To stop the Telnet BBS server, simply terminate the script by pressing Ctrl + C in the terminal or command prompt where the script is running.

### Customization
Feel free to customize the script to fit your specific requirements or add additional features as needed. You can modify the server settings, adjust the command handling logic, or add new commands to extend the functionality of the BBS.

### License
This Telnet BBS Server script is licensed under the GNU General Public License (GPL). See the LICENSE file for details.
