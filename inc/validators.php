<?php
// validators.php

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validateFlightData($data) {
    return isset($data['departure']) && isset($data['arrival']) && isset($data['date']);
}

function validateBookingForm($formData) {
    return validateEmail($formData['email']) && 
           validatePassword($formData['password']) && 
           validateFlightData($formData);
}
?>