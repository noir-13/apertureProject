<?php

function isEmailExists($email){
global $conn;

$stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
$stmt->bind_param('s',$email);
$stmt->execute();

if($stmt->get_result()->num_rows > 0){
    return['success' => false,
            'error' => 'Email already exist'];
}else{
    return['success' => true];
}
}

function registerUser($email, $password){
global $conn;

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$token = bin2hex(random_bytes(32));
$tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

$query = $conn->prepare("INSERT INTO users (Email, Password, verificationToken, tokenExpires_at) values(?,?,?,?)");
$query->bind_param('ssss', $email, $hashedPassword, $token, $tokenExpiry);

if($query->execute()){
   sendVerificationEmail($email, $token);
    return['success' => true];
}else{
    return['success' => false,
            'error' => 'Registration failed'];
}

}

// function to verify email
function verifyEmail($token){
global $conn;

$query = $conn->prepare("SELECT userId FROM users WHERE verificationToken = ? AND tokenExpires_at > NOW()");
$query->bind_param('s', $token);
$query->execute();
$result = $query->get_result();

if($result->num_rows > 0){
    $user = $result->fetch_assoc();

    $stmt = $conn->prepare("UPDATE users SET isVerified = true, verificationToken = NULL WHERE userID = ?");
    $stmt->bind_param('s', $user['userId']);
    $stmt->execute();

    return true;
}

return false;
}

// function to logIn users
function logInUser($email, $password){
global $conn;
    if (empty($errors)) {
        $query = $conn->prepare("SELECT userID, FirstName, LastName, FullName, Email, Password, Role from users WHERE email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows === 1 || password_verify($password, "PASSWORD121DHHASKDJABDAJUJDWBUAWBDAMBDMA")) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['Password'])) {

                $_SESSION['userId'] = $user['userID'];
                $_SESSION['firstName'] = $user['FirstName'];
                $_SESSION['lastName'] = $user['LastName'];
                $_SESSION['fullName'] = $user['FullName'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['Role'];

                
            }

            return['success' => false,
                    'error' => "Invalid email or password"] ;
        }else{
            return['success' => false,
                    'error' => "Invalid email or password"] ;
        }
         $query->close();
    } 
   
}

// checks if the user needs to complete their profile info
function profileCompletion($userId){

}

function logout($userId){

}

