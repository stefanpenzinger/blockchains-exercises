package at.fh.hagenberg.blockchains;

import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;

public class HashRestClient {
    private static final String BASE_URL = "http://localhost:4710";
    private static final String HASH_REST_HEADER_NAME = "HashREST";
    private static final String GREET_ENDPOINT = BASE_URL + "/greet";
    private static final String LIST_ENDPOINT = BASE_URL + "/list";
    private static final String UPLOAD_ENDPOINT = BASE_URL + "/upload";
    private final HttpClient httpClient;
    private final ProofOfWorkGenerator proofOfWorkGenerator;

    public HashRestClient() {
        httpClient = HttpClient.newHttpClient();
        proofOfWorkGenerator = new ProofOfWorkGenerator();
    }

    /**
     * Sends a GET request to the /greet endpoint
     *
     * @return The response of the server
     */
    public HttpResponse<String> greet() throws IOException, InterruptedException {
        var request = HttpRequest.newBuilder()
                .header(HASH_REST_HEADER_NAME, proofOfWorkGenerator.generate(1, GREET_ENDPOINT))
                .uri(URI.create(GREET_ENDPOINT))
                .GET()
                .build();

        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }

    /**
     * Sends a GET request to the /list endpoint
     *
     * @return The response of the server
     */
    public HttpResponse<String> list() throws IOException, InterruptedException {
        var request = HttpRequest.newBuilder()
                .header(HASH_REST_HEADER_NAME, proofOfWorkGenerator.generate(3, LIST_ENDPOINT))
                .uri(URI.create(LIST_ENDPOINT))
                .GET()
                .build();

        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }

    /**
     * Sends a POST request to the /upload endpoint
     *
     * @return The response of the server
     */
    public HttpResponse<String> upload() throws IOException, InterruptedException {
        var request = HttpRequest.newBuilder()
                .header(HASH_REST_HEADER_NAME, proofOfWorkGenerator.generate(5, UPLOAD_ENDPOINT))
                .uri(URI.create(UPLOAD_ENDPOINT))
                .POST(HttpRequest.BodyPublishers.noBody())
                .build();

        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }
}
