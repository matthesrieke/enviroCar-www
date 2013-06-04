<?
include('header.php');
require_once('assets/includes/connection.php');
?>

<script type="text/javascript">
  //Javascript function to receive GET variables (Syntax: $_GET(['variableName']) ) 
  (function(){
    var s = window.location.search.substring(1).split('&');
      if(!s.length) return;
        var c = {};
        for(var i  = 0; i < s.length; i++)  {
          var parts = s[i].split('=');
          c[unescape(parts[0])] = unescape(parts[1]);
        }
      window.$_GET = function(name){return name ? c[name] : c;}
  }())
  
  function init(){
    user = $_GET(['user']);
    loggedInUser = '<?php echo $_SESSION["name"] ?>';
    $('#username').html(user);
    getUserFriends();
    getUserGroups();
    getLoggedInUserFriends();
  }


  function getUserGroups(){
    $.get('./assets/includes/users.php?groupsOf='+user, function(data) {
      if(data == 400 || data == 401 || data == 402 || data == 403 || data == 404){
          error_msg("Groups couldn't be loaded successfully.");
      }else{
        data = JSON.parse(data);
        if(data.groups.length > 0 ){
          $('#groups').append('<ul style="margin-bottom: 10px; overflow-y:auto">');
          for(i = 0; i < data.groups.length; i++){
            $('#groups').append('<li class="customLi"><a href="group.php?group='+data.groups[i].name+'">'+data.groups[i].name+'</a></li>');
          }
          $('#groups').append('</ul>');
        }
      }
    });
  }

  function getLoggedInUserFriends(){
    $.get('./assets/includes/users.php?friendsOf='+loggedInUser, function(data) {
      if(data == 400 || data == 401 || data == 402 || data == 403 || data == 404){
          error_msg("Friends couldn't be loaded successfully.");
      }else{
        data = JSON.parse(data);
        if(data.users.length > 0 ){
          for(i = 0; i < data.users.length; i++){
            if(user == data.users[i].name){
              $('#addAsFriendLink').html('<a href="javascript:removeAsFriend();">Remove as Friend</a>');
              break;
            }
            else{ 
              $('#addAsFriendLink').html('<a href="javascript:addAsFriend();">Add as Friend</a>');
            }
          }
        }
        else{ 
              $('#addAsFriendLink').html('<a href="javascript:addAsFriend();">Add as Friend</a>');
        }

      }
    });
  }

  function getUserFriends(){
    $.get('./assets/includes/users.php?friendsOf='+user, function(data) {
      if(data == 400 || data == 401 || data == 402 || data == 403 || data == 404){
          error_msg("Friends couldn't be loaded successfully.");
      }else{
        data = JSON.parse(data);
        if(data.users.length > 0 ){
          $('#friends').html("");
          for(i = 0; i < data.users.length; i++){
            $('#friends').append('<li class="customLi" style="list-style-type:none; min-height: 25px;"><img src="./assets/img/person.svg" style="height: 30px; margin-right: 10px; float:right; "/><a href="profile.php?user='+data.users[i].name+'">'+data.users[i].name+'</a></li>');
          }
        }

      }
    });
  }

  function removeAsFriend(){
    $.post('./assets/includes/users.php?deleteFriend', {deleteFriend: user}, 
      function(data){
        if(data == 400 || data == 401 || data == 402 || data == 403 || data == 404){
          error_msg("Friend couldn't be removed.");
        }else{
          $('#addAsFriendLink').html('<a href="javascript:addAsFriend();">Add as Friend</a>');
        }
      });
  }

  function addAsFriend(){
    $.post('./assets/includes/users.php?addFriend', {addFriend: user}, 
      function(data){
        if(data == 400 || data == 401 || data == 402 || data == 403 || data == 404){
          error_msg("Friend couldn't be added successfully.");
        }else{
          $('#addAsFriendLink').html('<a href="javascript:removeAsFriend();">Remove as Friend</a>');
        }
      });
  }

  function deleteAccount(){
    if(confirm("Are you sure you want to delete your account? This can't be undone!")){
        $.post('./assets/includes/authentification.php?delete', {delete: true }, 
            function(data){
              console.log(data);
              if(data === 'status:ok'){
                window.location.href = "index.php?deleted";
              }else{
                error_msg("Deletion has failed. Please try again");
              }
      });
    }
  }
  
  $(function () {
    init();
});

</script>
 
<div class="container rightband">
  <div class="row-fluid">
    <div class="span4">
      <div class="well sidebar-nav">
        <ul class="nav nav-list">
          <img src="./assets/img/user.jpg" align="center" style="height: 100px; margin-right: 100px; "/>
          <li class="nav-header">Community</li>
          <li>Username:    <b id="username"></b></li>
          <?
            if($_GET['user'] == $_SESSION['name']){
              echo '<p><a href="javascript:deleteAccount();" class="btn btn-primary btn-small">Delete my Account &raquo;</a></p>';
            }else{
              echo '<li id="addAsFriendLink"></li>';
            }
          ?>
        </ul>
      </div><!--/.well -->
    </div><!--/span-->

    <div class="span8">
      <div class="span4">
        <h2>Friends</h2>
        <ul id="friends" style="margin-bottom: 10px; overflow-y:auto">
        </ul>
      </div>
      <div id="groups" class="span4">
        <h2>Groups</h2>
      </div>
      <div id="licensing" class="span4">
        <h2>Licensing</h2>
        
            <div class="btn-group">
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
				License all new data as:
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<li><a href="#">Private</a></li>
					<li><a href="#">Open DataBase License</a></li>
				</ul>
			</div>
      </div>
    </div>
  </div>
</div>
      
<?
include('footer.php');
?>
      
         
        
        

     

     
    


    

