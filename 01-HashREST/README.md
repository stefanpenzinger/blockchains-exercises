# Assignment #1: Implement HashCash for a ReST service

## How to test implementation
Do the following in order to test the application:
1. Execute the gradle task `start-hash-rest-server` under the group development in order to start the HashREST server.
2. Execute the main class `at.fh.hagenberg.HashRestCli` in order to start the CLI for the HashRestClient.

## Assignment Goal & Environment
Implement the **HashCash** principle for a ReST service: **HashREST**
* The goal is to implement a ReST interface, whose endpoints are only usable (i.e. requests are
  properly served) if and only if the corresponding HTTP request contains a valid proof of work value
* Use any programming language and/or libraries in order to create a ReST API and HTTP requests 
* Define a few ReST endpoints, i.e. **/greet/**, **/list/** and **/upload/**
* Each HTTP request sent to the ReST service must include an **HTTP Header** called
  **HashREST: \<value\>**
* If the HashREST value is missing or corrupt, the response from the web-server should contain an
  error message

A client needs to create a number of semicolon-separated values for each request (see next slide), and
subsequently iterate through a loop which
* increments a counter (the last value in the list),
* hashes the entire string,
* checks if difficulty requirements were met.

## Implementation
HTTP request **HashREST** header looks like the following string:
**HashREST: \<time-stamp\>;\<URL\>;\<random\>;\<counter\>**
* **\<timestamp\>:** UNIX timestamp value
* **\<URL\>:** Service URL e.g. http://example.com/greet/
* **\<random\>:** 6 randomly generated characters (a-z), different for each request
* **\<counter\>:** The counter which the client needs to determine which, when joined with the previous string and hashed, results in a valid proof of work

Use SHA256 as hash function, and a freely configurable numeric difficulty value (e.g. for a difficulty of 2,
the first two characters need to be zero).
Each REST endpoint may require a different difficulty, i.e. **/greet/** only requires a difficulty of 1 while
**/upload/** requires a difficulty of 3, so uploading is "harder" than just saying hello.