<!doctype html>
<html>
    <head>
        <title>Please wait</title>
    </head>
    <body>
        <p>Redirecting to CranberryMail Installer</p>
        <script>
            let url = window.location.href;
            url = url.split("/");
            if(url[url.length-1]==""){
                url.pop();
            }
            url.push("install");

            window.location = url.join("/");
        </script>
    </body>
</html>