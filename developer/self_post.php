<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14/10/2016
 * Time: 3:30 PM
 */
echo '<pre>';
print_r($_SERVER);
print_r($_POST);

if (!isset($_POST['handler']))
{
?>
<form method="post" action="">
    <input type="hidden" name="handler" value="test" >
    <input type="text" name="username" value="" >
    <input type="submit">
</form>

<h1>I\'m Caller</h1><br>
<?php
}
else
{
    echo '<h1>I\'m Handler</h1><br>';
    print_r(getallheaders());
}
