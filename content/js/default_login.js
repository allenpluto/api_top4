/**
 * Created by User on 1/12/2016.
 */
$('input[name="username"]').keydown(function(event){
    switch (event.which)
    {
        case 13:
            // Key Enter Pressed
            event.preventDefault();
            $('input[name="password"]').focus();
    }
});

$('input[name="password"]').keydown(function(event){
    switch (event.which)
    {
        case 13:
            // Key Enter Pressed
            event.preventDefault();
            $('input[type="submit"]').click();
    }
});
