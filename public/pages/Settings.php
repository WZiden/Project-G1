<h3 id="pageUsername">Username: </h3>

<p id="settingsStatusMessage" class="errorMessage"></p>

<form id="changeNameForm" class="pageForm">
    <fieldset>
        <legend>Change your username</legend>
        <input type="hidden" name="type" value="changeName">
        <label for="newUserName">New user name</label>
        <div class="inputContainer">
            <input type="text" placeholder="Enter your new username" id="newUserName" name="newUserName"
                autocomplete="on" required>
            <span class="material-symbols-outlined">person</span>
        </div>
        <button type="submit" class="login-btn">Change username</button>        
    </fieldset>
</form>


 <form id="changePasswordForm" class="pageForm">
    <fieldset>
        <legend>Change your password</legend>
        <p>The password must conain at least one lowercase letter (a-z), one uppercase letter (A-Z), one digit, and it must be at least 8 characters long.</p>
        <input type="hidden" name="type" value="changePassword">
        <label for="oldPassword">Your old password</label>
        <div class="inputContainer">
            <input type="password" placeholder="Enter your old password" id="oldPassword" name="oldPassword" required>
            <span class="material-symbols-outlined">lock</span>
        </div>
        <label for="newPassword">Your new password</label>
        <div class="inputContainer">
            <input type="password" placeholder="Enter your new password" id="newPassword" name="newPassword" required>
            <span class="material-symbols-outlined">lock</span>
        </div>
        <label for="confirmPassword">Confirm your new password</label>
        <div class="inputContainer">
            <input type="password" placeholder="Confirm your new password" id="confirmPassword" name="confirmPassword" required>
            <span class="material-symbols-outlined">lock</span>
        </div>
        <button type="submit" class="login-btn">Change password</button>        
    </fieldset>
</form>
