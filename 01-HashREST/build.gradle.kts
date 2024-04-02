plugins {
    id("java")
}

group = "blockchains"
version = "1.0.0"

repositories {
    mavenCentral()
}

dependencies {
    implementation("commons-codec:commons-codec:1.16.1")

    testImplementation(platform("org.junit:junit-bom:5.9.1"))
    testImplementation("org.junit.jupiter:junit-jupiter")
}

tasks.test {
    useJUnitPlatform()
}

tasks.register<Exec>("start-hash-rest-server") {
    group = "development"
    description = "Starts the HashREST server."
    commandLine("node", "app.js")
}