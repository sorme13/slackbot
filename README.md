

# slackBot


> this module is created to interact with slack

> the framework used for building this module is: swoole 
 
---
## ✨routes

the module routes are:
  - **/** 
    - the root is for checking client_id to know which slack app is connecting

  - **/redirect**
    - it will redirect you to https://slack.com/oauth/v2/authorize with two get parameters to get the code 
    - method:get
    - parameters: *client_id* and *scope* 
    - code is required for requesting token 
    - 🔃🔃in this stage you have to define a **"redirect url"** to your app in slack in this module **"/code"**  *(for example: http://localhost:9501/code)* is defined route for "redirect url"
   
  - **/code**
    - it will receive the code from slack and send it to "/auth" route which is for getting token    
    
  - **/auth**
    - it will send authentication request to slack 
    - method : post 
    - parameters :  client_id ,client_secret ,code
    
  
  - **/reqscope**
     - this is for requesting permissions scopes 
     - this is like "/redirect" and has the same method and parameters
     
     
  - **/home**
    - if you are authenticated( have token) you will be redirect to home page 
    - this page shows the list of channels that make it easier to test the module 
    
  - **/history**
    - this is for showing history of a channel 
    - method: post
    - parameters: token ,channel
      
  - **/sendmsg**
    - this is for sending message to a channel 
    - method: post
    - parameters: token ,channel_id
       
  - **/joinchannel**
    - this is for joining  to a channel 
    - method: post
    - parameters: token ,channel_id  
    
        
  - **/deletemsg**
    - this is for deleting a specific message 
    - method: post
    - parameters: token ,channel,timestamp
    - timestamp is the time of the message which we will delete and in will autofill by module when you click on delete button 
      
  - **/showreplies**
     - this is for showwing replies of a specific message 
     - method: post
     - parameters: token ,channel,timestamp
     - timestamp is the time of the message which we will see replies and in will autofill by module when you click on show reply button
     
  - **/reaction**
     - this is for reacting to a specific message 
     - method: post
     - parameters: token ,channel,timestamp,name (this is the name of emoji for example :smile)
     - timestamp is the time of the message which we will react and in will autofill by module when you click on reaction button 
       
  - **/channelinfo**
     - this is for showing channel information
     - method: post
     - parameters: token ,channel
          
  - **/channeleave**
     - this is for leaving a channel
     - method: post
     - parameters: token ,channel
             
  - **/removereaction**
     - this is for removing a reaction
     - method: post
    - parameters: token ,channel,timestamp,name (this is the name of emoji for example :smile)
      
  - **/getreaction**
     - this is for showing  reactions of a specific message
     - method: post
     - parameters: token ,channel,timestamp
   
  
 
## ⚡ get started

  - at first you need te import the database  that is  **slack.sql** file in the root directory of project and name it "slack"
  - if it is not on localhost you need to config **$conn** in **app.php** file
  - then you need to run app.php file to start http server
  - by starting with "/" route in your browser, you will define your slack app ( by client_id) and get authenticated in module stages
  - you need to build a slack app and install it on https://api.slack.com  and note client_id , client_secret 
  
  


