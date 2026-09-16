\# Secure Web-Based Password Manager



\## Scope

This project is a web-based secure password manager developed for the ICS0027 Web Application Security course. 

It focuses on the secure storage of credentials using server-side encryption, robust user authentication, and active defense mechanisms against common web vulnerabilities such as SQL injection, XSS, and CSRF.



\## Tech Stack

\* \*\*Backend:\*\* PHP

\* \*\*Database:\*\* MySQL

\* \*\*Frontend:\*\* HTML, CSS, JavaScript

\* \*\*Infrastructure:\*\* Docker \& Docker Compose



\## Features \& Planned Routes

\*\*Key Features:\*\*

\* Secure user authentication (Argon2id password hashing).

\* Server-side symmetric encryption for all stored vault credentials.

\* Secure session management (short expiration, strict cookie flags, fixation prevention).

\* Protections against SQLi (Prepared Statements), XSS (CSP, input validation), and CSRF (Tokens).



\*\*Planned Routes :\*\*

\* `/register` - User registration.

\* `/login` - User authentication and key derivation.

\* `/home` - The main password vault interface.

\* `/settings` - User account configuration.

\* `/logout` - Secure session termination.

\* `/api/vault` - Endpoints for adding, retrieving, and deleting encrypted credentials.



\## How to Run Locally

The application is containerized using Docker and is configured to run over TLS (HTTPS) in the development environment.



1\. Ensure you have Docker and Docker Compose installed on your machine.

2\. Clone this repository.

3\. Navigate to the project root directory in your terminal.

4\. Build and start the containers in detached mode:

&#x20;  ```bash

&#x20;  docker-compose up -d --build

&#x20;  ```

5\. Access the application in your web browser at: https://localhost.

