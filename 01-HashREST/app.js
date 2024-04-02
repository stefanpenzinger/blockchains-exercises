const express = require('express');
const bodyParser = require('body-parser');
const crypto = require('crypto');

const app = express();
const PORT = 4710;
const HASH_REST_HEADER = "HashREST";

app.set('port', PORT);
app.use(bodyParser.json());

/**
 * Calculates the hash from the given header
 * @param input
 * @returns {string}
 */
function calculateHash(input) {
  const hash = crypto.createHash('sha256');
  hash.update(input);
  
  return hash.digest('hex');
}

/**
 * Checks if the rest hash is valid for the given difficulty
 * @param difficulty
 * @param hashRestHeader
 * @returns {boolean}
 */
function isRestHashValid(difficulty, hashRestHeader) {
  const pattern = new RegExp("^0{" + difficulty + "}.*");
  const hashRest = calculateHash(hashRestHeader);
  
  return pattern.test(hashRest);
}

/**
 * Encapsulates the rest hash server functionality
 * @param req
 * @param res
 * @param difficulty
 * @returns {*}
 */
function restHash(req, res, difficulty) {
  const hashRestHeader = req.header(HASH_REST_HEADER);
  
  if (hashRestHeader == null) {
    return res.status(400).send(HASH_REST_HEADER + " is missing.");
  }
  
  if (!isRestHashValid(difficulty, hashRestHeader)) {
    return res.status(400).send("Proof of work not satisfied. (difficulty: " + difficulty + ")");
  }
  
  console.log(req.header(HASH_REST_HEADER))
}

app.get('/greet', (req, res) => {
  const difficulty = 1;
  
  restHash(req, res, difficulty);
  
  res.send('Hi!');
});


app.get('/list', (req, res) => {
  const difficulty = 3;
  
  restHash(req, res, difficulty);
  
  res.send('I have nothing to list lol.');
});

app.post('/upload', (req, res) => {
  const difficulty = 5;
  
  restHash(req, res, difficulty);
  
  res.send('Actually, I do not support uploading.');
});


app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
