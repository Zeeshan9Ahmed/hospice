const express = require('express');
const app = express();
var fs = require('fs');
var mysql = require("mysql");

// var admin = require("firebase-admin");
// var serviceAccount = require("./firebase.json");
// admin.initializeApp({
//   credential: admin.credential.cert(serviceAccount),
// //   databaseURL: "https://sample-project-e1a84.firebaseio.com"
// });
const notification_options = {
    priority: "high",
    timeToLive: 60 * 60 * 24
  };

const options = {
    // key: fs.readFileSync('/home/server1appsstagi/ssl/keys/cbb31_f063d_d5b565cbcfcbc295fe10a5520022d252.key'),
    // cert: fs.readFileSync('/home/server1appsstagi/ssl/certs/server1_appsstaging_com_cbb31_f063d_1685317033_bec1d832816c5b1d60d4a39c67efcc6f.crt'),
    key: fs.readFileSync('/home/server1appsstagi/ssl/keys/e75a1_2251f_1203307d340c5981b1cc59104c59cc22.key'),
    cert: fs.readFileSync('/home/server1appsstagi/ssl/certs/server1_appsstaging_com_e75a1_2251f_1690587434_0679a92f648f548fa488b341c060a707.crt'),
};

const server = require('https').createServer(options, app);
var io = require('socket.io')(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST","PATCH","DELETE"],
        credentials: false,
        transports: ['websocket', 'polling'],
        allowEIO3: true
    },
});

var con_mysql = mysql.createPool({
    host              :   'localhost',
    user              :   'server1appsstagi_hospice',
    password          :   'iISsEj)i+DE9',
    database          :   'server1appsstagi_hospice',
    debug             :   true,
    // charset:'utf8mb4'
});

var FCM = require('fcm-node');
var serverKey = 'AAAAQn8vSX4:APA91bEIF1ciu-36QQxIrolcjUxmD4KLzhFQSptposJ8xBEUgbZbJm9HqasQx1jLea_7UTClkgGIFIxGT4DJ0xiX4g7epg7_mV_hRaG3rQWlMIO3LxhuydRpmQguPxe7LIjUwxuXEkFr'; //put your server key here
var fcm = new FCM(serverKey);


// app.get('/', (req, res) => {
//   res.sendFile(__dirname + '/chat.html');
// });

io.on('connection', (socket) => {
  console.log('a user connected');

  console.log("JOINED ",socket.id);

     // GET MESSAGES EMIT
    socket.on('get_messages',function(object){

        var user_room = "user_"+object.sender_id;
        socket.join(user_room);

        get_messages(object,function(response){
            if(response){
                console.log("get_messages has been successfully executed...",response);
                console.log("sender is"+object.sender_id , "receiver is"+object.receiver_id);
                io.to(user_room).emit('response', {object_type:"get_messages", data:response});
            }else{
                console.log("get_messages has been failed...");
                io.to(user_room).emit('error', {object_type:"get_messages", message:"There is some problem in get_messages..."});
            }
        });
    });


    // SEND MESSAGE EMIT
    socket.on('send_message',function(object){
       /*console.log(object);*/
        var sender_room = "user_"+object.sender_id;
        var receiver_room = "user_"+object.receiver_id;
        socket.join(sender_room);
        socket.join(receiver_room);
        console.log(object.sender_id);
        console.log(object.receiver_id);
        
        if(object.sender_id === object.receiver_id){
            console.log("sender and receiver cant be same");
        }else{
        send_message(object,function(response){
            if(response){
                // console.log("send_message has been successfully executed...");
                // console.log(response);
                // io.to(sender_room).to(receiver_room).emit('response', {object_type:"get_message", data:response[0]});
                // // get_setting(object,function(setting_response){
                // //     console.log(setting_response);
                // //   if(setting_response.length>0){
                // //       //if data available, check on and off
                // //       if(setting_response[0].notification == 'on')
                // //       {
                // //           var msg = object.message;
                // //           var user_name="";
                // //           sender_user(object,function(response3){
                // //           user_name =response3[0].username;
                // //           });
                // //           //Push Notification
                // //           get_user_token(object,function(response2){

                // //               if(response2.length>0){
                // //                    const message_notification = {
                // //                    notification: {
                // //                   title: user_name+" send you a message.",
                // //                    body: msg,
                // //                    // sender_id : object.sender_id,
                // //                    // receiver_id : object.receiver_id,
                // //                        },
                // //                        data:{
                // //                           type:"chat",
                // //                            // // 'booking_id':""+booking_id
                // //                            //  'other_id':""+object.sender_id
                // //                           title: user_name+" send you a message.",
                // //                           body: msg

                // //                    }
                // //                           };
                // //                   //Push Notification
                // //                   const  registrationToken =response2[0].user_device_token;
                // //                   const options =  notification_options
                // //                     admin.messaging().sendToDevice(registrationToken, message_notification, options);
                // //               }

                // //           })
                // //       }
                // //   }
                // //   else{
                // //       //if settings of data is not available,by default notification will be on
                // //       var msg = object.message;
                // //           var user_name="";
                // //           sender_user(object,function(response3){
                // //           user_name =response3[0].username;
                // //           });
                // //           //Push Notification
                // //           get_user_token(object,function(response2){

                // //               if(response2.length>0){
                // //                    const message_notification = {
                // //                    notification: {
                // //                   title: user_name+" send you a message.",
                // //                    body: msg
                // //                        },
                // //                        data:{
                // //                           type:"chat",
                // //                            // // 'booking_id':""+booking_id
                // //                            //  'other_id':""+object.sender_id
                // //                           title: user_name+" send you a message.",
                // //                           body: msg

                // //                    }
                // //                           };
                // //                   //Push Notification
                // //                   const  registrationToken =response2[0].user_device_token;
                // //                   const options =  notification_options
                // //                     admin.messaging().sendToDevice(registrationToken, message_notification, options);
                // //               }

                // //           })
                // //   }
                // // });
                
                //Push Notification
                // get_user_token(object,function(response2){
                    
                //     console.log(response2[0]['device_token']);
                // })
                console.log(response[0]['user_device_token']);
                if(response[0]['user_device_token'] === null){
                    io.to(sender_room).to(receiver_room).emit('response', { object_type: "get_message", data: response[0] });
                    console.log("Successfully sent with response: ");
                }else{
                    var name="";
                    if(response[0]['role'] === 'nurse')
                    {
                        name = response[0]['first_name']
                    }
                    else
                    {
                        name = response[0]['business_name']
                    }
                    
                    var message = { //this may vary according to the message type (single recipient, multicast, topic, et cetera)
                        to: response[0]['user_device_token'], 
                        collapse_key: 'your_collapse_key',
                        
                        notification: {
                            title:'Chat Notification',
                            body:name+' Send you a message',
                           // user_name: response[0]['full_name'],
                            type:'chat',
                            redirection_id:object.sender_id,
                            vibrate:1,
                            sound:1
                        },
                        
                        data: {  //you can send only notification or only data(or include both)
                            title:'Chat Notification',
                            body:name+' Send you a message',
                            //user_name: response[0]['user_name'],
                            type:'CHAT',
                            redirection_id:object.sender_id,
                            vibrate:1,
                            sound:1
                        }
                    };
                
                    fcm.send(message, function(err, response_two){
                        if (err) {
                            console.log("Something has gone wrong!");
                            io.to(sender_room).to(receiver_room).emit('response', { object_type: "get_message", data: response[0] });
                        } else {
                            console.log("send_message has been successfully executed...");
                            io.to(sender_room).to(receiver_room).emit('response', { object_type: "get_message", data: response[0] });
                            console.log(response[0]);
                           // console.log("Successfully sent with response: ", response_two);
                        }
                    });
                }
                
            }
            else{
                console.log("send_message has been failed...");
                io.to(sender_room).to(receiver_room).emit('error', {object_type:"get_message", message:"There is some problem in get_message..."});
            }
        });
        }
    });

});


// GET MESSAGES FUNCTION
var get_messages = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`SELECT u.first_name, u.business_name, u.profile_image, c.*
                                FROM users AS u
                                JOIN chats AS c
                                ON u.id = c.sender_id
                                WHERE (c.sender_id = '${object.sender_id}' AND c.receiver_id = '${object.receiver_id}')
                                OR (c.sender_id = '${object.receiver_id}' AND c.receiver_id = '${object.sender_id}') ORDER BY c.id ASC`, function(error,data){


                connection.release();
                if(error){
                    callback(false);
                }else{
                    callback(data);
                }
            });
        }
    });
};



// VERIFY LIST FUNCTION
var verify_list = function(message,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`SELECT * from conversations where (sender_id = ${message.sender_id} AND receiver_id = ${message.receiver_id} ) OR (receiver_id = ${message.sender_id} AND sender_id = ${message.receiver_id})  LIMIT 1 `, function(error,data){
                if(error){
                    callback(false);
                }else{
                        var today = new Date();
                        var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
                        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                        var dateTime = date+' '+time;
                        console.log(dateTime);
                    if(data.length === 0){

                        connection.query(`INSERT INTO conversations (sender_id , receiver_id , type , last_message , created_at)
                        VALUES ('${message.sender_id}' , '${message.receiver_id}', '${message.type}' , '${message.message}' , '${dateTime}')`, function(error,data){
                            connection.release();
                            if(error){
                                callback(false);
                            }else{
                                callback(data.insertId);
                            }
                        });
                    }
                    else{
                        //counter code
                        // var dbcounter = data[0].counter;
                        // console.log(dbcounter);
                        // var counter = ++dbcounter;

                        connection.query(`UPDATE  conversations SET last_message= '${message.message}', type= '${message.type}', created_at = '${dateTime}' WHERE id = ${data[0].id}`, function(error,data){
                            connection.release();
                            if(error){
                                callback(false);
                            }
                        });
                        callback(data[0].id);
                    }
                }
            });
        }
    });
};

// SEND MESSAGE FUNCTION
var send_message = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{

            verify_list(object,function(response){
                if(response){
                    // var new_message = mysql_real_escape_string (object.message);
                     console.log("insert into chats has been successfully executed...");
                     var today = new Date();
                        var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
                        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                        var dateTime = date+' '+time;
                         console.log(dateTime);
                    connection.query(`INSERT INTO chats SET sender_id = '${object.sender_id}', receiver_id = '${object.receiver_id}', type = '${object.type}' ,message = '${object.message}' , conversation_id = ${response} , created_at = '${dateTime}'`, function(error,data){
                    //connection.query(`INSERT INTO chats SET sender_id = 1, receiver_id = 2, message = 'hello'`, function(error,data){
                        if(error){
                            console.log("error",error);
                            connection.release();
                            callback(false);

                        }else{

                            console.log("success")
                            // connection.release();




                            // connection.query(`SELECT u.name, u.profile_image, c.*
                            //     FROM users AS u
                            //     JOIN chats AS c
                            //     ON u.id = c.receiver_id
                            //     WHERE c.id = '${data.insertId}'`, function(error,data_record){
                            //     connection.release();
                            //     if(error){
                            //         callback(false);
                            //     }else{
                            //         console.log(data_record);
                            //         callback(data_record);
                            //     }
                            // });




                            connection.query(`SELECT u.role,u.first_name, u.business_name, u.profile_image, u.device_token,
                            (select device_token from users where id = '${object.receiver_id}') as user_device_token, c.*
                                FROM users AS u
                                JOIN chats AS c
                                ON u.id = c.sender_id
                                WHERE c.conversation_id = '${response}' ORDER BY c.id DESC`, function(error,data){
                                connection.release();
                                if(error){
                                    callback(false);
                                }else{
                                    console.log("response data : ---------".data);
                                    // const propertyNames = Object.keys(data);
                                    // console.log(propertyNames);
                                    callback(data);
                                }
                            });

                        }
                    });


                }
                else{
                    console.log("verify_list has been failed...");
                    callback(false);
                }

            });

        }
    });
};



//Push Notification

var get_user_token = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`select * from users where id=${object.receiver_id}`, function(error,data){
                connection.release();
                if(error){
                    callback(error);
                }else{
                    callback(data);
                }
            });
        }
    });
};

var sender_user = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`select * from users where id=${object.sender_id}`, function(error,data){
                connection.release();
                if(error){
                    callback(error);
                }else{
                    callback(data);
                }
            });
        }
    });
};

// var get_setting = function(object,callback){
//     con_mysql.getConnection(function(error,connection){
//         if(error){
//             callback(false);
//         }else{
//             connection.query(`select * from settings where user_id=${object.receiver_id}`, function(error,data){
//                 connection.release();
//                 if(error){
//                     callback(error);
//                 }else{
//                     callback(data);
//                 }
//             });
//         }
//     });
// };

function mysql_real_escape_string (str) {
    return str.replace(/[\0\x08\x09\x1a\n\r"'\\\%]/g, function (char) {
        switch (char) {
            case "\0":
                return "\\0";
            case "\x08":
                return "\\b";
            case "\x09":
                return "\\t";
            case "\x1a":
                return "\\z";
            case "\n":
                return "\\n";
            case "\r":
                return "\\r";
            case "\"":
            case "'":
            case "\\":
            case "%":
                return "\\"+char; // prepends a backslash to backslash, percent,
                                  // and double/single quotes
            default:
                return char;
        }
    });
}

server.listen(3039, () => {
  console.log('listening on *:3039');
});
