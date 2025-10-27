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
    <title>Home</title>
</head>
<body>



  
     
  <section class="w-100 min-vh-100 p-5 justify-content-center align-content-center" >

  <?php if(isset($_SESSION['fullName'])): ?>
  <h1 class="bg-light text-dark"><?php echo $_SESSION['fullName']; ?></h1>
  <?php endif ?>  

  <a data-bs-toggle="modal" data-bs-target="#confirmModal"  class="btn btn-danger">Logout</a>

  <div class="modal fade" id="confirmModal" data-bs-backdrop="static">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Confirmation</h4>
          <button type="button" data-bs-dismiss="modal" class="btn btn-close"></button>
        </div>
        <div class="modal-body">

        <?php if(isset($_SESSION['fullName'])): ?>
          <p>Sir <?php echo $_SESSION['fullName']; ?>, Do you want to log out?</p>
          <?php endif ?>
        </div>

        <div class="modal-footer">
          <button class="btn" data-bs-dismiss="modal">Cancel</button>
          <a href="logout.php" class="btn btn-danger">Log out</a>
        </div>
      </div>
    </div>
  </div>

    </section>

   <?php include './includes/footer.php'; ?>

     <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
  <script src="script.js"></script>
</body>
</html>