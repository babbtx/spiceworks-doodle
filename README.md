## Welcome

This is a tutorial written for app developers learning the 
[Spiceworks Cloud App API](http://developers.spiceworks.com/documentation/cloud-apps).
Spiceworks Cloud Apps are hosted web applications that can be embedded within any installation
of Spiceworks, interacting with the data managed by Spiceworks, and adding value to the
day-to-day of the millions of IT Pros who use [Spiceworks](http://www.spiceworks.com).
 
## Background

At some point in your Spiceworks Cloud App development journey you're going to hit the point where you want
to store your own data about the users or resources using your app. Because the Spiceworks SDK does not allow 
you to store your own data alongside Spiceworks data in the hosting application, this means you're going to 
need your own data store. In this tutorial we are going to build an application that merges data from your own 
data store into the Spiceworks application.

There are many data storage options out there today. You could consider using a file or data storage as a 
service option like Firebase or Parse. For this tutorial we are going to build our own application server using 
MySQL as the database--the venerable LAMP stack.

> *DISCLAIMER* 
> I am not a PHP developer, nor do I claim to be one on TV. In fact, this is the first PHP I've written.
> This tutorial was written for developers who may already be familiar with using PHP for storing
> and retrieving data from a database and rendering that data in HTML. If you are not familiar with PHP,
> this tutorial can still apply to you as you will learn how to retrieve environment and ticket data
> via the Spiceworks API.

## Tutorial

What you need to get started:

1. A Linux server or VM or a working LAMP server.
2. Patience and determination.

Some stuff you'll learn in this tutorial:

1. PHP
2. SQL
3. jQuery
4. Spiceworks SDK
5. Patience and determination.

Here's what we're going to build

Inspired by [Austin's Graffiti Park](https://www.google.com/#q=austin+grafitti+park), let's build an application 
to allow Help Desk admins to doodle on the tickets on which they are working. Here's the end result:

![Doodle of a mouse and trap](/images/finished.png)

Let's get started!

### 1. Build a LAMP server.

Follow an online tutorial to configure a typical "LAMP stack"--Linux, Apache, MySQL, PHP. 
Be sure to include phpMyAdmin.

Most tutorials end with creating a `phpinfo()` page. Here's my result:

![phpinfo screenshot](/images/phpinfo.png)

### 2. Using phpMyAdmin, create a database and table.

For this tutorial, I'm going to create a generic table that could be used to store any key and value pair 
about a Spiceworks Help Desk ticket. Later on we're only going to use it to store two specific key/value 
pairs per ticket, but that's OK. You can imagine using it for more features.

First login to phpMyAdamin at http://your-server/phpmyadmin.

Create our database:

![create database screenshot](/images/create_database.png)

Create our table:

![create table screenshot](/images/create_table.png)

Fill in the table column definitions. The pair of application uid (`auid`) and `ticket_id` will identify the 
specific ticket to which the data applies. The other columns `key` and `value` are how we will store
our data:

![define four column table screenshot](/images/define_table.png)

### 3. Create single-page application to post data to server.

In my LAMP server the default website's files are stored in /var/www/html. Yours may differ. 
Create two files with the contents below.

Our application has an editable title area and an HTML5 Canvas on which we can draw. When the user clicks 
on the submit button, the browser submits a form on the page. The form contains the image title, the image data, 
plus a placeholder for the future application and ticket information. The form submit will POST the content 
to the server where we will next write a form handler in PHP.

For right now, the form submission hard-codes the application uid and ticket id. Also, this example uses AJAX 
to submit the form rather than actually having the browser submit the form. This is really personal preference, 
and you don't necessarily have to do it this way, but I take advantage of it later in the tutorial.

Why is my form completely hidden? I used a hidden form because the user is never entering the doodle information 
directly into any form. He or she draws on a canvas elsewhere in the HTML, and then Javascript fills in the hidden 
form and submits it. Quite honestly, in my application the form is entirely unnecessary, but if you are building an
application to take other user input you would likely have a visible form.

#### app.html

```html
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="app.css">
  </head>
  <body class="container">

    <div id="partial-container" class="content-section">
      <section>
        <heading><h2>Untitled</h2></heading>
      </section>
    </div>

    <section class="content-section drawing-container clearfix">
      <canvas id="canvas"></canvas>
      <div class="pull-right">
        <button data-action="clear" class="btn">Clear</button>
        <button data-action="save" class="btn btn-primary">Save</button>
      </div>
    </section>
    
    <form id="hidden-form" action="save_image.php" method="post" class="hidden">
      <input type="hidden" name="ticket" value="1" />
      <input type="hidden" name="auid"  value="foo" />
      <input type="hidden" name="title" />
      <input type="hidden" name="image" />
    </form>

    <script type="text/javascript" src="http://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="http://jimdoescode.github.io/jqScribble/jquery.jqscribble.js"></script>
    <script type="text/javascript" src="https://spiceworks.github.io/spiceworks-js-sdk/dist/spiceworks-sdk.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){
        $('#canvas').jqScribble();

        // switch header into "edit mode" on click
        // do this with delegating handler because the event handlers are
        // removed when using $.html() to replace partial contents
        $('#partial-container').on('click', 'h2', function(evt){
          $(this).addClass('editing');
          $(this).attr('contenteditable', true);
          $(this).focus();
        });

        // reset the canvas on 'clear'
        $('.drawing-container .btn[data-action="clear"]').on('click', function(evt){
          $('#canvas').jqScribble.clear();
        });

        // save the title and image to the server on 'save'
        $('.drawing-container .btn[data-action="save"]').on('click', function(evt){
          var btn = $(this);
          $('#canvas').jqScribble.save(function(data){
            btn.attr('disabled',true);
            // copy the title from the title section to the form
            var title = $('#partial-container heading').text();
            title = $.trim(title);
            $('#hidden-form input[name="title"]').attr('value', title);
            // save the image data to the form
            $('#hidden-form input[name="image"]').attr('value', data);
            // we could have the browser submit the form which would make the browser display the action result
            // but we're going to ignore the result html and just submit via AJAX
            var form = $('#hidden-form');
            $.post(form.attr('action'), form.serialize())
             .done(function(){
               $('#partial-container h2').removeClass('editing');
             })
             .always(function(){
               btn.attr('disabled',false);
             });
          });
        });

      });
    </script>
  </body>
</html>
```

#### app.css

```css
#partial-container h2 {
  border-bottom: 1px solid silver;
  padding: 3px;
}
#partial-container h2.editing {
  box-shadow: inset 0px 0px 1px 1px rgba(50,50,50,0.2);
  outline: none;
}
.content-section {
  margin:auto;
  margin-top:20px;
  width:540px;
}
#saved-image {
  display:none;
}
.drawing-container {
  border:1px solid silver;
  height:400px;
}
.btn + .btn {
  margin-left: 2px;
}
```

### 4. Test what we have so far.

If you load http://your-server/app.html in your browser you should get an untitled canvas.
If you doodle something on the canvas and submit you should get a 404 error in the browser's developer tools.

![screenshot of empty canvas](/images/empty_canvas.png)

### 5. Create form submit handler in PHP.

Next we create a PHP page that accepts the form submission via POST and stores the form data in the database.

The page will establish a connection to the database, sanitize the form input, and then either INSERT or UPDATE 
rows in the database. The end result will be two rows in the database. Both will have the same application uid 
and ticket number to uniquely identify the ticket within the Spiceworks application. The first row will contain
the key `image_title` and the value will be the title as entered by the user. The second row will contain the key
`image_data` and the value will be the base-64 encoded PNG image data as returned from the HTML5 Canvas.

Normally when you build a page to accept form data from a browser the page would redirect after accepting 
the form input. For example, a login form would take you to the landing page of your application, or a survey 
form would take you to a "Thank you" page. Because I'm submitting the form via AJAX (above), 
redirection is unnecessary. The Javascript that submits the form will decide what happens next based on the 
HTTP response code of 200 Success or 400 Bad Request.

#### save_image.php

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "spiceworks_app_data");
$auid = $mysqli->escape_string($_POST["auid"]);
$ticket = (int) $_POST["ticket"];
$image_title = $mysqli->escape_string($_POST["title"]);
$image_data = $mysqli->escape_string($_POST["image"]);
$result = $mysqli->query("SELECT value FROM extra_ticket_data WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_title'");
if ($result->num_rows > 0) {
  $sql1 = "UPDATE extra_ticket_data SET value = '$image_title' WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_title'";
  $sql2 = "UPDATE extra_ticket_data SET value = '$image_data' WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_data'";
  if (!$mysqli->query($sql1)) {
    http_response_code(400);
    echo "\nupdate error: " . $mysqli->sqlstate;
    echo "\nupdate error: " . $mysqli->error;
  }
  elseif (!$mysqli->query($sql2)) {
    http_response_code(400);
    echo "\nupdate error: " . $mysqli->sqlstate;
    echo "\nupdate error: " . $mysqli->error;
  }
  else {
    echo "updated";
  }
}
else {
  $sql1 = "INSERT INTO extra_ticket_data (`auid`, `ticket_id`, `key`, `value`) VALUES ('$auid', $ticket, 'image_title', '$image_title')";
  $sql2 = "INSERT INTO extra_ticket_data (`auid`, `ticket_id`, `key`, `value`) VALUES ('$auid', $ticket, 'image_data', '$image_data')";
  if (!$mysqli->query($sql1)) {
    http_response_code(400);
    echo "\ninsert error: " . $mysqli->sqlstate;
    echo "\ninsert error: " . $mysqli->error;
  }
  elseif (!$mysqli->query($sql2)) {
    http_response_code(400);
    echo "\ninsert error: " . $mysqli->sqlstate;
    echo "\ninsert error: " . $mysqli->error;
  }
  else {
    echo "created";
  }
}
$result->free();
?>
```

### 6. Test what we have so far.

Point your browser to http://your-server/app.html, doodle something and submit the form.

![screenshot of smile on canvas](/images/smile_canvas_edit.png)

Login to myPhpAdmin again and use the Browse feature to see the contents of your database. 
What you should see is something like this:

![screenshot of table browse](/images/browse_table.png)

Did it work? Great job! If not, open the browser's developer tools and set a Javascript debugger breakpoint 
and try again. Note that jqScribble's `save()` function will not callback our function unless you've actually 
doodled something in the canvas.

![screenshot of javascript breakpoint](/images/jsdebugger.png)

### 7. Build a page in PHP to display the image.

Nowadays Javascript MVC frameworks like Ember and Angular have popularized the model where servers extract 
data from their databases, return data via REST APIs and JSON, and pretty much the entire HTML UI is assembled 
via the Javascript running in the user's browser. Back in its prime, the PHP way was to have the server extract 
data from its database, then marry the data with HTML code in the server, and then return the full HTML UI to 
the browser. While we could build a PHP page to return JSON data about our ticket's doodle, instead we are going 
to build a page more familiar to PHP developers.

This page will establish a connection to the database, load the image title and data from the database using 
the query string parameters that identify the target application uid and ticket number, 
and then render an HTML snippet like this:

```html
<section>
  <heading>
    <h2>Smile</h2>
  </heading>
</section>
<img>
```

#### saved_image.php

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "spiceworks_app_data");
$auid = $mysqli->escape_string($_GET["auid"]);
$ticket = (int) $_GET["ticket"];
?>
<section>
  <heading>
    <h2>
<?php
$result = $mysqli->query("SELECT value FROM extra_ticket_data WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_title'");
$row = $result->fetch_assoc();
if ($row) {
  $value = $row['value'];
  echo htmlentities($value);
}
else {
  http_response_code(404);
  echo "Not found";
}
$result->free();
?>
    </h2>
  </heading>
<?php
$result = $mysqli->query("SELECT value FROM extra_ticket_data WHERE auid = '$auid' AND ticket_id = $ticket AND `key` = 'image_data'");
$row = $result->fetch_assoc();
if ($row) {
  $value = $row['value'];
  echo "<img id='saved-image' src='$value'>";
}
$result->free();
?>
</section>
```

### 8. Test our new page.

Point your browser to http://your-server/saved_image.php?auid=foo&ticket=1

![the smile image only](/images/smile_image_only.png)

### 9. Load the saved image in our application.

When our application loads inside the Spiceworks Help Desk we want the image that was previously doodled to appear. 
Now let's do literally that: add Javascript to the page that, upon load, requests the HTML snippet of the saved 
image and inserts it into the page.

Here we add a Javascript function called `loadTicketImage()` that loads the HTML snippet from the server and then
uses jQuery's `html()` function to replace the HTML contents of the container we identify by id `partial-container`.
For now the page is still hard-coded to application uid `foo` and ticket number `1`, 
only now I've moved it out of the hidden form and into the Javascript.

#### app.html

```html
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="app.css">
  </head>
  <body class="container ">

    <div id="partial-container" class="content-section">
      <section>
        <heading><h2>Loading&hellip;</h2></heading>
      </section>
    </div>

    <section class="content-section drawing-container clearfix">
      <canvas id="canvas"></canvas>
      <div class="pull-right">
        <button data-action="clear" class="btn">Clear</button>
        <button data-action="save" class="btn btn-primary">Save</button>
      </div>
    </section>
    
    <form id="hidden-form" action="save_image.php" method="post" class="hidden">
      <input type="hidden" name="ticket" />
      <input type="hidden" name="auid" />
      <input type="hidden" name="title" />
      <input type="hidden" name="image" />
    </form>

    <script type="text/javascript" src="http://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="http://jimdoescode.github.io/jqScribble/jquery.jqscribble.js"></script>
    <script type="text/javascript" src="https://spiceworks.github.io/spiceworks-js-sdk/dist/spiceworks-sdk.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){
        $('#canvas').jqScribble();

        // switch header into "edit mode" on click
        // do this with delegating handler because the event handlers are
        // removed when using $.html() to replace partial contents
        $('#partial-container').on('click', 'h2', function(evt){
          $(this).addClass('editing');
          $(this).attr('contenteditable', true);
          $(this).focus();
        });

        // reset the canvas on 'clear'
        $('.drawing-container .btn[data-action="clear"]').on('click', function(evt){
          $('#canvas').jqScribble.clear();
        });

        // save the title and image to the server on 'save'
        $('.drawing-container .btn[data-action="save"]').on('click', function(evt){
          var btn = $(this);
          $('#canvas').jqScribble.save(function(data){
            btn.attr('disabled',true);
            // copy the title from the title section to the form
            var title = $('#partial-container heading').text();
            title = $.trim(title);
            $('#hidden-form input[name="title"]').attr('value', title);
            // save the image data to the form
            $('#hidden-form input[name="image"]').attr('value', data);
            // we could have the browser submit the form which would make the browser display the action result
            // but we're going to ignore the result html and just submit via AJAX
            var form = $('#hidden-form');
            $.post(form.attr('action'), form.serialize())
             .done(function(){
               $('#partial-container h2').removeClass('editing');
             })
             .always(function(){
               btn.attr('disabled',false);
             });
          });
        });

        // this function requests the php-rendered html from the server and loads it into the page
        // it also takes the image returned from the server and loads the image data into the canvas
        // it returns the promise so the caller can also react to success or failure
        function loadTicketImage(ticket, auid) {
          return $.get('/saved_image.php', {'ticket': ticket, 'auid': auid}, 'text/html')
           .done(function(html){
             // load the partial html into the page
             $('#partial-container').html(html);
             // load the png image itself into the canvas
             var image = $('#partial-container img')[0];
             $('#canvas')[0].getContext('2d').drawImage(image, 0, 0);
           });
        }

        // init the page
        var auid = 'foo';
        var ticket = 1;
        $('#hidden-form input[name="auid"]').attr('value', auid);
        $('#hidden-form input[name="ticket"]').attr('value', ticket);
        loadTicketImage(ticket, auid)
          // on failure (most likely no image) set to "Untitled"
          .fail(function(){
            $('#partial-container h2').text("Untitled");
          });

      });
    </script>
  </body>
</html>
```

### 10. Test what we have so far.

Point your browser to http://your-server/app.html and smile as it loads your previous drawing
from your server database.

![screenshot of smile on canvas](/images/smile_canvas_show.png)

### 11. Embed inside the Spiceworks application.

Define your application inside of the Spiceworks Developer Edition. Click Spiceworks in the title bar, 
then New App, New Platform App, and fill in the forms like this:

![screenshot of app form](/images/new_app_form.png)

We're only interested in displaying our application inside of the Help Desk. 
Eventually we might want to build a full page placement. For now we'll point the full page placement 
to the homepage of our PHP server.

Navigate to a Help Desk ticket by clicking Apps in the nav bar, then Help Desk. Your application's tab 
is available in the ticket body.

![screenshot of smile on canvas embedded on ticket](/images/smile_canvas_embedded.png)

### 12. Load and save the doodle's specific to the ticket.

Now we've finally come to the point where we stop hard-coding application uid `foo` and ticket `1`! 
Using the Spiceworks SDK we will extract the application uid from the environment when our application is activated,
and using the helpdesk service we will respond to the `showTicket` event to know the ticket number on which 
our application is displayed. 

In my initialization logic I've added a feature to init the title of the new image whenever the server responds 
with no previously saved image for the ticket. The new image title is "Inspired by" followed by the summary 
of the ticket. I extract the summary using the helpdesk service API. 
This illustrates how you might use or copy Spiceworks data to your database, depending on your application needs.

#### app.html

```html
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="app.css">
  </head>
  <body class="container ">

    <div id="partial-container" class="content-section">
      <section>
        <heading><h2>Loading&hellip;</h2></heading>
      </section>
    </div>

    <section class="content-section drawing-container clearfix">
      <canvas id="canvas"></canvas>
      <div class="pull-right">
        <button data-action="clear" class="btn">Clear</button>
        <button data-action="save" class="btn btn-primary">Save</button>
      </div>
    </section>
    
    <form id="hidden-form" action="save_image.php" method="post" class="hidden">
      <input type="hidden" name="ticket" />
      <input type="hidden" name="auid" />
      <input type="hidden" name="title" />
      <input type="hidden" name="image" />
    </form>

    <script type="text/javascript" src="http://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="http://jimdoescode.github.io/jqScribble/jquery.jqscribble.js"></script>
    <script type="text/javascript" src="https://spiceworks.github.io/spiceworks-js-sdk/dist/spiceworks-sdk.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){
        $('#canvas').jqScribble();

        // switch header into "edit mode" on click
        // do this with delegating handler because the event handlers are
        // removed when using $.html() to replace partial contents
        $('#partial-container').on('click', 'h2', function(evt){
          $(this).addClass('editing');
          $(this).attr('contenteditable', true);
          $(this).focus();
        });

        // reset the canvas on 'clear'
        $('.drawing-container .btn[data-action="clear"]').on('click', function(evt){
          $('#canvas').jqScribble.clear();
        });

        // save the title and image to the server on 'save'
        $('.drawing-container .btn[data-action="save"]').on('click', function(evt){
          var btn = $(this);
          $('#canvas').jqScribble.save(function(data){
            btn.attr('disabled',true);
            // copy the title from the title section to the form
            var title = $('#partial-container heading').text();
            title = $.trim(title);
            $('#hidden-form input[name="title"]').attr('value', title);
            // save the image data to the form
            $('#hidden-form input[name="image"]').attr('value', data);
            // we could have the browser submit the form which would make the browser display the action result
            // but we're going to ignore the result html and just submit via AJAX
            var form = $('#hidden-form');
            $.post(form.attr('action'), form.serialize())
             .done(function(){
               $('#partial-container h2').removeClass('editing');
             })
             .always(function(){
               btn.attr('disabled',false);
             });
          });
        });

        // this function requests the php-rendered html from the server and loads it into the page
        // it also takes the image returned from the server and loads the image data into the canvas
        // it returns the promise so the caller can also react to success or failure
        function loadTicketImage(ticket, auid) {
          return $.get('/saved_image.php', {'ticket': ticket, 'auid': auid}, 'text/html')
           .done(function(html){
             // load the partial html into the page
             $('#partial-container').html(html);
             // load the png image itself into the canvas
             var image = $('#partial-container img')[0];
             $('#canvas')[0].getContext('2d').drawImage(image, 0, 0);
           });
        }

        // init the page
        var auid = null;
        var ticket = null;
        var card = new SW.Card();

        // get the application id from the environment
        card.onActivate(function(env){
          auid = env.app_host.auid;
          $('#hidden-form input[name="auid"]').attr('value', auid);
        });

        // get the ticket id from the helpdesk service event
        card.services('helpdesk').on('showTicket', function(id){
          ticket = id;
          $('#hidden-form input[name="ticket"]').attr('value', ticket);
          // load the ticket
          loadTicketImage(ticket, auid)
            // on failure (most likely no image) use the ticket summary
            .fail(function(){
              card.services('helpdesk').request('ticket', ticket).then(function(data){
                var title = "Inspired by \"" + data.summary + "\"";
                $('#partial-container h2').text(title);
              });
            });
        });

      });
    </script>
  </body>
</html>
```

### 13. Doodle away!

You've made it! Test your new application by switching to different tickets, doodling, and saving your masterpieces!

![Doodle of a mouse and trap](/images/finished.png)

