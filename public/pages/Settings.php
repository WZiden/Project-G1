<div class="history-mode-buttons">
    <button id="userSettings">Settings</button>
    <button id="adminSettings">Admin</button>
</div>

<h3 id="pageUsername">Username: </h3>
<h3 id="pageAdmin">Admin page</h3>

<p id="settingsStatusMessage" class="errorMessage"></p>

<!-- User settings -->
<div id="userView">
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
        <p>The password must contain at least one lowercase letter (a-z), one uppercase letter (A-Z), one digit, and it must be at least 8 characters long.</p>
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
</div>

<!-- Admin stuff -->
<div id="adminView">
    <section>
        <h3>All users</h3>
        <ul id="userList"></ul>        
    </section>
    <section>
        <h3>All Devices</h3>
        <ul id="deviceList"></ul>        
    </section>


    <form id="changeUserForm" class="pageForm">
        <fieldset>
            <legend>Manage users</legend>
            <input type="hidden" name="type" value="changeUser">
            <label for="newUserPassword">New password</label>
            <div class="inputContainer">
                <input type="password" placeholder="Enter a new password" id="newUserPassword" name="newUserPassword" required>
                <span class="material-symbols-outlined">lock</span>
            </div>
            <label for="confirmUserPassword">Confirm new password</label>
            <div class="inputContainer">
                <input type="password" placeholder="Confirm the new password" id="confirmUserPassword" name="confirmUserPassword" required>
                <span class="material-symbols-outlined">lock</span>
            </div>
            <button type="submit" class="login-btn">Confirm</button>        
        </fieldset>
    </form>
</div>
