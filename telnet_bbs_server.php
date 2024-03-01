<?php
/**
 * PHP Telnet BBS Server
 * 
 * This code is part of a Telnet Bulletin Board System (BBS) server implementation.
 * It allows multiple clients to connect over Telnet, register, and log in to the BBS.
 * Registered users can execute commands such as help and exit.
 * 
 * This specific implementation is based on the GPL-licensed code written by Federico Saccà in 2024.
 * 
 * @author Federico Saccà
 * @license GPL (GNU General Public License)
 * @link https://www.federicosacca.it
 * @link https://www.gnu.org/licenses/gpl-3.0.html
 */


class Server
{
    private $host;
    private $port;
    private $serverSocket;
    private $clients = [];
    private $pdo;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->initializeDatabase();
    }

    private function initializeDatabase()
    {
        $dbFile = 'database.sqlite';
        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->createUsersTable();
    }

    private function createUsersTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nickname VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL
                )";
        $this->pdo->exec($sql);
    }

    public function start()
    {
        $this->createServerSocket();
        echo "Telnet BBS started at telnet://{$this->host}:{$this->port}\n";

        while (true) {
            $this->handleIncomingConnections();
            $this->handleClientInteractions();
        }
    }

    private function createServerSocket()
    {
        $this->serverSocket = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
        if (!$this->serverSocket) {
            die("Error: $errstr ($errno)\n");
        }
    }

private function handleIncomingConnections()
{
    $read = $this->clients;
    $read[] = $this->serverSocket;

    if (false === ($numChangedStreams = @stream_select($read, $write, $except, 0))) {
        die("Error in stream_select()\n");
    }

    if ($numChangedStreams > 0) {
        if (in_array($this->serverSocket, $read)) {
            if (false !== ($newClient = @stream_socket_accept($this->serverSocket))) {
                $this->clients[] = new Client($newClient, $this->pdo);
                echo "New client connected\n";
                $this->sendBannerMessage($newClient);
                unset($read[array_search($this->serverSocket, $read)]);
            } else {
                echo "Error accepting new client\n";
            }
        }
    }
}

private function handleClientInteractions()
{
    foreach ($this->clients as $key => $client) {
        $socket = $client->getSocket();
        if (is_resource($socket) && !feof($socket)) {
            $input = rtrim(fgets($socket, 1024));
            if ($client->isAuthenticated()) {
                $this->handleAuthenticatedClientInput($client, $input);
            } else {
                $this->handleUnauthenticatedClientInput($client, $input);
            }
        } else {
            // Client disconnected, clean up
            fclose($socket);
            unset($this->clients[$key]);
            echo "Client disconnected\n";
        }
    }
}


    private function handleAuthenticatedClientInput($client, $input)
    {
        switch ($input) {
            case 'exit':
                fclose($client->getSocket());
                echo "Client disconnected\n";
                unset($this->clients[$key]);
                break;
            case 'help':
                $client->sendMessage("Available commands: exit, help\n");
                break;
            default:
                $client->sendMessage("Unknown command. Type 'help' for available commands.\n");
                break;
        }
    }

    private function handleUnauthenticatedClientInput($client, $input)
    {
        switch ($input) {
            case 'register':
                $client->register();
                break;
            case 'login':
                $client->login();
                break;
            default:
                $client->sendMessage("Invalid input. Please try again.\n");
                break;
        }
    }

    private function sendBannerMessage($client)
    {
        $bannerMessage = "

 
*********************************************************************
*                                                                   *
*   _____ _          _____     _         _      _____ _____ _____   *
*  |  _  | |_ ___   |_   _|___| |___ ___| |_   | __  | __  |   __|  *
*  |   __|   | . |    | | | -_| |   | -_|  _|  | __ -| __ -|__   |  *
*  |__|  |_|_|  _|    |_| |___|_|_|_|___|_|    |_____|_____|_____|  *
*          |_|                                                      *
*                                                                   *
*********************************************************************
*                                                                   *
*                 Welcome to Php Telnet BBS Server!                 *
*                                                                   *
*                   To register, type 'register'.                   *
*                       To login, type 'login'.                     *
*                                                                   *
*********************************************************************
";
        fwrite($client, $bannerMessage);
    }
}

class Client
{
    private $socket;
    private $authenticated = false;
    private $pdo;

    public function __construct($socket, $pdo)
    {
        $this->socket = $socket;
        $this->pdo = $pdo;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    public function authenticate()
    {
        $this->authenticated = true;
    }

    public function sendMessage($message)
    {
        fwrite($this->socket, $message);
    }

public function register()
{
    $this->sendMessage("Enter nickname: ");
    $nickname = rtrim(fgets($this->socket, 1024));
    $this->sendMessage("Enter password: ");
    $password = rtrim(fgets($this->socket, 1024));

    if (!empty($nickname) && !empty($password)) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE nickname = ?");
        $stmt->execute([$nickname]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $this->sendMessage("Nickname already exists. Please choose a different one.\n");
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (nickname, password) VALUES (?, ?)");
            $stmt->execute([$nickname, $hashedPassword]);
            $this->sendMessage("Registration successful. You are now logged in.\n");
            $this->sendMessage("Type 'help' for available commands.\n");
            $this->authenticate(); // Automatically log in after registration
        }
    } else {
        $this->sendMessage("Invalid input. Please try again.\n");
    }
}

public function login()
{
    $this->sendMessage("Enter nickname: ");
    $nickname = rtrim(fgets($this->socket, 1024));
    $this->sendMessage("Enter password: ");
    $password = rtrim(fgets($this->socket, 1024));

    if (!empty($nickname) && !empty($password)) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE nickname = ?");
        $stmt->execute([$nickname]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $this->authenticate();
            $this->sendMessage("Login successful. Welcome, $nickname!\n");
            $this->sendMessage("Type 'help' for available commands.\n");
        } else {
            $this->sendMessage("Invalid nickname or password. Please try again.\n");
            fclose($this->socket); // Close client connection
        }
    } else {
        $this->sendMessage("Invalid input. Please try again.\n");
        fclose($this->socket); // Close client connection
    }
}

}

$server = new Server('0.0.0.0', 2324);
$server->start();
