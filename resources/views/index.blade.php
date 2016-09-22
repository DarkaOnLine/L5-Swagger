<?php

if (app()->environment() != 'testing') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With");
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('l5-swagger.api.title')}}</title>
    <link rel="icon" type="image/png" href="{{config('l5-swagger.paths.assets_public')}}/images/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="{{config('l5-swagger.paths.assets_public')}}/images/favicon-16x16.png" sizes="16x16" />
    <link href='{{config('l5-swagger.paths.assets_public')}}/css/typography.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='{{config('l5-swagger.paths.assets_public')}}/css/reset.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='{{config('l5-swagger.paths.assets_public')}}/css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='{{config('l5-swagger.paths.assets_public')}}/css/reset.css' media='print' rel='stylesheet' type='text/css'/>
    <link href='{{config('l5-swagger.paths.assets_public')}}/css/print.css' media='print' rel='stylesheet' type='text/css'/>
    
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/object-assign-pollyfill.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/jquery-1.8.0.min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/jquery.slideto.min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/jquery.wiggle.min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/handlebars-4.0.5.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/lodash.min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/backbone-min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/swagger-ui.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/highlight.9.1.0.pack.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/highlight.9.1.0.pack_extended.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/jsoneditor.min.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/marked.js' type='text/javascript'></script>
    <script src='{{config('l5-swagger.paths.assets_public')}}/lib/swagger-oauth.js' type='text/javascript'></script>

    <!-- Some basic translations -->
    <!-- <script src='lang/translator.js' type='text/javascript'></script> -->
    <!-- <script src='lang/ru.js' type='text/javascript'></script> -->
    <!-- <script src='lang/en.js' type='text/javascript'></script> -->

    <script type="text/javascript">
        $(function () {
            var url = window.location.search.match(/url=([^&]+)/);
            if (url && url.length > 1) {
                url = decodeURIComponent(url[1]);
            } else {
                url = "{!! $urlToDocs !!}";
            }
    
            hljs.configure({
                highlightSizeThreshold: {{ $highlightThreshold }}
            });

            // Pre load translate...
            if(window.SwaggerTranslator) {
                window.SwaggerTranslator.translate();
            }
            window.swaggerUi = new SwaggerUi({
                url: url,
                dom_id: "swagger-ui-container",
                @if(array_key_exists('validatorUrl', get_defined_vars()))
                // This differentiates between a null value and an undefined variable
                validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
                @endif
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                onComplete: function(swaggerApi, swaggerUi){
                    @if(isset($requestHeaders))
                    @foreach($requestHeaders as $requestKey => $requestValue)
                    window.swaggerUi.api.clientAuthorizations.add("{{$requestKey}}", new SwaggerClient.ApiKeyAuthorization("{{$requestKey}}", "{{$requestValue}}", "header"));
                    @endforeach
                            @endif

                    if(typeof initOAuth == "function") {
                        initOAuth({
                            clientId: "your-client-id",
                            clientSecret: "your-client-secret-if-required",
                            realm: "your-realms",
                            appName: "your-app-name",
                            scopeSeparator: " ",
                            additionalQueryStringParams: {}
                        });
                    }

                    if(window.SwaggerTranslator) {
                        window.SwaggerTranslator.translate();
                    }
                    
                },

                onFailure: function(data) {
                    console.log("Unable to Load SwaggerUI");
                },
                docExpansion: {!! isset($docExpansion) ? '"' . $docExpansion . '"' : '"none"' !!},
                jsonEditor: false,
                defaultModelRendering: 'schema',
                showRequestHeaders: false
            });
            
            window.swaggerUi.load();
            
        });
    </script>
</head>

<body class="swagger-section">
<div id='header'>
    <div class="swagger-ui-wrap">
        <a id="logo" href="http://swagger.io"><img class="logo__img" alt="swagger" height="30" width="30" src="{{config('l5-swagger.paths.assets_public')}}/images//logo_small.png" /><span class="logo__title">swagger</span></a>
        <form id='api_selector'>
            <div class='input'><input placeholder="http://example.com/api" id="input_baseUrl" name="baseUrl" type="text"/></div>
            <div id='auth_container'></div>
            <div class='input'><a id="explore" class="header__btn" href="#" data-sw-translate>Explore</a></div>
        </form>
    </div>
</div>

<div id="message-bar" class="swagger-ui-wrap" data-sw-translate>&nbsp;</div>
<div id="swagger-ui-container" class="swagger-ui-wrap"></div>
</body>
</html>
