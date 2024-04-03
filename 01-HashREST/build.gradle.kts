plugins {
    id("java")
}

group = "blockchains"
version = "1.0.0"

repositories {
    mavenCentral()
}

val commonsCodecVersion = "1.16.1"

dependencies {
    implementation("commons-codec:commons-codec:$commonsCodecVersion")

    testImplementation(platform("org.junit:junit-bom:5.9.1"))
    testImplementation("org.junit.jupiter:junit-jupiter")
}

tasks.test {
    useJUnitPlatform()
}

tasks.register<Exec>("install-node-modules") {
    group = "development"
    description = "Installs the Node.js modules."
    commandLine("npm", "install")
}

tasks.register<Exec>("start-hash-rest-server") {
    group = "development"
    description = "Starts the HashREST server."

    dependsOn("install-node-modules")
    commandLine("node", "app.js")
}

tasks.register("cleanNodeModules") {
    group = "development"
    description = "Cleans the Node.js modules."
    doLast {
        project.delete("node_modules")
    }
}

tasks.clean {
    dependsOn("cleanNodeModules")
}