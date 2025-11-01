<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Complete your profile - Aperture</title>
</head>

<body>
    <!-- <?php include './includes/header.php'; ?> -->




    <section class="w-100 min-vh-100  p-0 p-sm-2  d-flex justify-content-center align-items-center" id="reg">

        <div class="container justify-content-center px-4 p-md-3">
            <div class="row justify-content-center align-items-center bg-white shadow p-3 rounded-5  ">

                <div class="col position-relative">
                   

                    <form action="" method="POST" class="p-4">

                        <div class="text-center mb-3">
                            <h1 class=" display-5 m-0 serif">Complete your profile</h1>
                            <small>Join Aperture today and enjoy seamless booking, transparent pricing, and trusted pros at your fingertips.</small>
                        </div>

                        <!-- First and Last Name  -->

                        <div class="mb-2 d-flex gap-2 flex-column flex-md-row ">
                            <div class="w-100">
                                <label for="fname" class="form-label">First name<span class="text-danger">*</span></label>
                                <input type="text" name="fname" id="fname" class="form-control <?php echo (!isset($errors['fname']) ? '' : 'is-invalid')  ?> " placeholder="e.g., Prince Andrew" required>
                            </div>
                            <div class="w-100">
                                <label for="lname" class="form-label">Last name<span class="text-danger">*</span></label>
                                <input type="text" name="lname" id="lname" class="form-control <?php echo (!isset($errors['lname']) ? '' : 'is-invalid')  ?> " placeholder="e.g., Casiano" required>
                            </div>
                        </div>

                        <!-- Phone  -->

                        <div class="mb-2">
                            <label class="form-label" for="email">Contact No.<span class="text-danger">*</span></label>
                            <input type="text" name="email" id="email" class="form-control <?php echo (!isset($errors['email']) ? '' : 'is-invalid')  ?> " placeholder="e.g., 09827386287" required>
                            <?php if (isset($errors['email'])): ?>
                                <p class="text-danger"><?php echo $errors['email'] ?></p>
                            <?php endif ?>
                        </div>

                        <!-- Password -->

                      

                        <!-- Check Terms and Condition -->

                        <div class="form-check mb-3">
                            <input type="checkbox" name="termsCheck" id="termsCheck" class="form-check-input" required>
                            <label for="termsCheck" id="termsLabel" class="form-check-label"><small>By creating an account, you confirm that you have read, understood, and agreed to the <a href="#" type="button" data-bs-toggle="modal" data-bs-target="#dataModal">Terms and Conditions and Privacy Notice.</a></small></label>
                        </div>

                        <?php include "./includes/modals/terms.php"?>

                     
                            <input type="submit" class="btn w-100 bg-dark text-light mb-1" value="Complete Profile">
                            

                    </form>
                </div>

                <div class="col d-none d-lg-inline p-4 rounded-4 bg-secondary overflow-hidden">
                    <img src="./assets/undraw_complete-form_aarh.svg" class="img-fluid object-fit-cover" alt="">
                </div>
            </div>
        </div>

    </section>


    <!-- <?php include './includes/footer.php'; ?> -->

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>



