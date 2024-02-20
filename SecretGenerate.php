<?php


// Generate a random JWT secret key
function generateRandomString($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}

$jwtSecretKey = generateRandomString();
echo $jwtSecretKey; // This will output the randomly generated JWT secret key
