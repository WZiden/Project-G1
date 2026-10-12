<!DOCTYPE html>
<html lang="sv">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mobiltest</title>
        <link rel="stylesheet" href="style.mobile.css">
      
    </head>
    <body>
        <div class="wrapper">
            <div class ="grid-container">
                
                    <header class="nav-top grid-box"> 
                        <div class="logo" ><h1>logo<h1></div>
                        <div class="button">
                            <button class = "room-btn" data-room ="login" > Login</button>
                            <button class = "room-btn" data-room ="search" > Search</button>              
                        </div>  
                    </header>

                <nav class="scrollbar grid-box">
                    <div class ="button">
                        <button class = "room-btn" data-room ="favorite" > Favorite</button>
                        <button class = "room-btn" data-room ="devices" > Devices</button>
                        <button class = "room-btn" data-room ="room1" > room1</button>
                        <button class = "room-btn" data-room ="room2" > room2</button>
                        <button class = "room-btn" data-room ="room3" > room3</button>
                        <button class = "room-btn" data-room ="room4" > room4</button>                    
                   
                    </div>

                </nav>

                <main class="content-area grid-box">
                    <div class= "cards">
                        <div class ="card">
                        <h2>Lamp1</h2>
                        <p> on</p>
                        <button class = "room-btn" > on/off</button>             
                        </div>
                        <div class ="card">
                        <h2>Lamp2</h2>
                        <p> on</p>
                        <button class = "room-btn" > on/off</button>             
                        </div>
                        <div class ="card">
                        <h2>Lamp3</h2>
                        <p> off</p>
                        <button class = "room-btn" > on/off</button>             
                        </div>
                        <div class ="card">
                        <h2>Lamp4</h2>
                        <p> on</p>
                        <button class = "room-btn" > on/off</button>             
                        </div>
                     
                    </div>
                    
                </main>

                <footer class ="nav-bottom grid-box">
                    <div class="button">
                        <button class = "room-btn" data-room ="history" > History</button>
                        <button class = "room-btn" data-room ="profile" > Profile</button> 
                        <button class = "room-btn" data-room ="logout" > Logout</button>            
                    </div>  

                </footer>
            </div>
            
        
        </div>

    </body>
</html>
