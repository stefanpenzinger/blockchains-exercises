package at.fh.hagenberg.blockchains;

import org.apache.commons.codec.digest.DigestUtils;

import java.time.Instant;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.ZonedDateTime;
import java.util.Random;

public class ProofOfWorkGenerator {
    private static final int RANDOM_STRING_LEN = 6;
    private static final ZoneId ZONE_ID = ZoneId.of("Europe/Vienna");
    private static final Random RANDOM = new Random();
    private static final int NUMBER_OF_LETTERS = 26;

    /**
     * @param difficulty The amount of leading zeros the hash has to match
     * @param url        The URL to which the request is sent to
     * @return The HashREST plain text [time-stamp];[URL];[random];[counter]
     */
    public String generate(int difficulty, String url) {
        var timestamp = ZonedDateTime.of(LocalDateTime.now(), ZONE_ID).toInstant();
        return loopUntilHashRestFinished(difficulty, timestamp, url, generateRandomString());
    }

    /**
     * Loop through the HashREST generation until the difficulty criteria has been matched.
     *
     * @param difficulty The amount of leading zeros the hash has to match
     * @param timestamp  The time when the HashREST is generated
     * @param url        The URL to which the request is sent to
     * @param random     A string consisting of 6 random characters (a-z)
     * @return The HashREST plain text [time-stamp];[URL];[random];[counter]
     */
    private String loopUntilHashRestFinished(int difficulty, Instant timestamp, String url, String random) {
        var delimiter = ";";
        var baseString = timestamp.toEpochMilli() + delimiter + url + delimiter + random + delimiter;
        var regexPattern = "^0{" + difficulty + "}.*";

        int counter = 0;
        var sha256hex = "";
        var hashRest = "";

        do {
            hashRest = baseString + counter++;
            sha256hex = DigestUtils.sha256Hex(hashRest);
        } while (!sha256hex.matches(regexPattern));

        System.out.println("\nHash found for " + hashRest + "\n" + sha256hex);

        return hashRest;
    }

    /**
     * @return A string consisting of 6 random characters (a-z)
     */
    private String generateRandomString() {
        var randomString = new StringBuilder();

        for (int i = 0; i < RANDOM_STRING_LEN; i++) {
            int randomNumber = RANDOM.nextInt(NUMBER_OF_LETTERS);
            char randomChar = (char) ('a' + randomNumber);

            randomString.append(randomChar);
        }

        return randomString.toString();
    }
}
