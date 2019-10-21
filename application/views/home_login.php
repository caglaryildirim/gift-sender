<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8"/>
    <title>Login Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
    <link rel="stylesheet" type="text/css" href="/css/main.css"/>
    <script src="/js/jquery-3.4.1.min.js"></script>
    <script src="/js/md5.js"></script>
</head>
<body>
<header>
    <h1>Welcome To My Game</h1>
</header>
<section>
    <form method="post" action="/home/login" id="frmLogin">
        <input type="hidden" name="password" id="txtPasswordPost"/>
        <p><label for="txtUsername">User name</label></p>
        <div><input type="email" name="username" id="txtUsername" maxlength="100" size="20" value="<?=htmlspecialchars($username)?>"/></div>
        <p><label for="txtPassword">Password</label></p>
        <div><input type="password" id="txtPassword" maxlength="100" size="20"/></div>
        <div><input type="submit" value="Login" id="loginButton"/></div>
    </form>
    <?php if (!empty($loginErrorMessage)): ?>
    <p class="errorMessage"><?=htmlspecialchars($loginErrorMessage)?></p>
    <?php endif; ?>
</section>
<script type="text/javascript">
    (function ($) {
        var loginPasswordSalt = <?=json_encode($passwordSalt)?>;
        $("#frmLogin").submit(function () {
            var txtUser = $("#txtUsername");
            if (txtUser.val() == "") {
                alert("Please enter username/email!");
                return false;
            }

            var txtPass = $("#txtPassword");
            var txtPassPost = $("#txtPasswordPost");
            var passwordHash = md5(loginPasswordSalt + md5(txtPass.val()) + loginPasswordSalt);
            txtPassPost.val(passwordHash)
            return true;
        });
    })(jQuery);
</script>
</body>
</html>