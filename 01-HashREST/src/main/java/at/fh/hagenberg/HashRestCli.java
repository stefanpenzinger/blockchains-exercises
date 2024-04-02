package at.fh.hagenberg;

import at.fh.hagenberg.blockchains.HashRestClient;

import java.io.IOException;
import java.net.http.HttpResponse;
import java.util.Scanner;

public class HashRestCli {
    public static void main(String[] args) throws IOException, InterruptedException {
        HashRestClient hashRestClient = new HashRestClient();

        String input;
        var scanner = new Scanner(System.in);

        System.out.println("--- HashREST Test Client ---");
        do {
            System.out.println("The following options are available: ");
            System.out.println("\t(1) /greet [difficulty 1]");
            System.out.println("\t(2) /list [difficulty 3]");
            System.out.println("\t(3) /upload [difficulty 5]");
            System.out.println("\t(0) Exit application");
            System.out.print("\nPlease chose a option: ");

            input = scanner.next();

            switch (input) {
                case "1":
                    printHttpResponse(hashRestClient.greet());
                    break;
                case "2":
                    printHttpResponse(hashRestClient.list());
                    break;
                case "3":
                    printHttpResponse(hashRestClient.upload());
                    break;
                case "0":
                    System.out.println("\nClosing application...");
                    break;
                default:
                    System.out.println("Option " + input + " not supported.");
            }
        } while (!input.equals("0"));
    }

    private static void printHttpResponse(HttpResponse<String> response) {
        System.out.println("\nResponse from " + response.uri() + " with status " + response.statusCode());
        System.out.println("Body:" + response.body());
    }
}
