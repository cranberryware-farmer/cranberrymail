<!doctype html>
<html>
    <head>
        <title>Please wait</title>
    </head>
    <body>
        <p>Redirecting to Cranberry Mail</p>
        <script>
            let url = window.location.href;
            url = url.split("/");
            if(url[url.length-1]==""){
                url.pop();
            }
            url.push("cmail");

            window.location = url.join("/");
        </script>
    </body>
</html>