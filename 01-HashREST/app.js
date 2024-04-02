const express = require('express');
const bodyParser = require('body-parser');
const crypto = require('crypto');

const app = express();
const PORT = 4710;
const HASH_REST_HEADER = "HashREST";
const NUMBER_OF_HEADER_PARTS = 4;
const HASH_ALGORITHM = "sha256";
const HEX_DIGEST = "hex";

app.set('port', PORT);
app.use(bodyParser.json());

/**
 * Calculates the hash from the given header
 * @param input {string} The input to be hashed
 * @returns {string} The hash of the input
 */
function calculateHash(input) {
  const hash = crypto.createHash(HASH_ALGORITHM);
  hash.update(input);
  
  return hash.digest(HEX_DIGEST);
}

/**
 * Checks if the rest hash is valid for the given difficulty
 * @param difficulty {number} The difficulty of the proof of work
 * @param hashRestHeader {string} The hash rest header
 * @returns {boolean} True if the proof of work is valid, false otherwise
 */
function isRestHashValid(difficulty, hashRestHeader) {
  if (hashRestHeader.split(";").length !== NUMBER_OF_HEADER_PARTS) {
    return false;
  }
  
  const pattern = new RegExp("^0{" + difficulty + "}.*");
  const hashRest = calculateHash(hashRestHeader);
  
  return pattern.test(hashRest);
}

/**
 * Checks if the timestamp is valid
 * @param timestamp {string} The timestamp when the work was done
 * @returns {boolean} True if the timestamp is valid, false otherwise
 */
function isTimestampValid(timestamp) {
  const currentDateTime = new Date();
  const timestampDateTime = new Date(parseInt(timestamp));
  const fiveMinutes = 5 * 60 * 1000;
  
  return currentDateTime - timestampDateTime <= fiveMinutes;
}

/**
 * Encapsulates the rest hash server functionality
 * @param req {Request} The request object
 * @param res {Response} The response object
 * @param difficulty {number} The difficulty of the proof of work
 * @param successBody {{message: string}} The success response body
 * @returns {Response} The response object
 */
function restHash(req, res, difficulty, successBody) {
  const hashRestHeader = req.header(HASH_REST_HEADER);
  let errorResponse = {};
  
  if (hashRestHeader == null) {
    errorResponse = { error: HASH_REST_HEADER + " is missing." };
    return res.status(400).send(errorResponse);
  }
  
  if (!isRestHashValid(difficulty, hashRestHeader)) {
    errorResponse = { error: HASH_REST_HEADER + " wrong or proof of work not satisfied. (difficulty: " + difficulty + ")" };
    return res.status(400).send(errorResponse);
  }
  
  const hashRestHeaderParts = hashRestHeader.split(";");
  
  if (!isTimestampValid(hashRestHeaderParts[0])) {
    errorResponse = { error: "Timestamp of " + HASH_REST_HEADER + " is invalid. (Must be within 5 minutes)" };
    return res.status(400).send(errorResponse);
  }
  
  return res.send(successBody);
}


app.get('/greet', (req, res) => {
  const difficulty = 1;
  const successBody = { message: 'Hi!' };
  
  return restHash(req, res, difficulty, successBody);
});


app.get('/list', (req, res) => {
  const difficulty = 3;
  const successBody = { message: 'I have nothing to list lol.' };
  
  return restHash(req, res, difficulty, successBody);
});

app.post('/upload', (req, res) => {
  const difficulty = 5;
  const successBody = { message: 'Whoops, forgot to tell you. File upload is actually not supported.' };
  
  return restHash(req, res, difficulty, successBody);
});


app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
