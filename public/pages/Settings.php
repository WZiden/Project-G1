<div class="history-mode-buttons">
    <button id="userSettings" data-mode="settings">Settings</button>
    <button id="adminSettings" data-mode="admin">Admin</button>
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

    <section class="tablecontainer">
        <table id="userTable">
            <caption>Users</caption>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody id="userTableBody"></tbody>
        </table>
    </section>

    <section class="tablecontainer">
        <table id="deviceTable">
            <caption>Devices</caption>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID</th>
                    <th>Required user level</th>
                </tr>
            </thead>
            <tbody id="deviceTableBody"></tbody>
        </table>
    </section>

</div>