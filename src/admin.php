<?php
session_start();
if (!isset($_SESSION['userId'])){
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Admin</title>
</head>
<body>
     <?php include './includes/header.php'; ?>

     <section class="w-100 min-vh-100 p-5 justify-content-center align-content-center" >

         <?php if(isset($_SESSION['fullName'])): ?>
         <h1 class="bg-light text-dark"><?php echo $_SESSION['firstName']; ?></h1>
         <?php endif ?>  

         <a href="logout.php" class="btn btn-danger">Logout</a>
         
    </section>

     </section>
    


     <?php include './includes/footer.php'; ?>
     <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
     <script src="script.js"></script>
</body>
</html>