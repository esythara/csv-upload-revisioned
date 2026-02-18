<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Tech Task - Street Group</title>
        @vite(['resources/scss/global.scss', 'resources/js/app.js'])

        <!-- FONTS -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
           
    </head>
    <body>

        <!-- HEADER -->
         <div class="outerContainer pos-fixed">
            <div class="innerContainer">
                <div class="innerItem is-flex is-between center-aligned">
                    <span>
                        <a href="/"><img src="https://cdn.prod.website-files.com/5e38423084bb96caf84a40ce/658d7d9c4205f2875438666e_street-group-logo-white.svg" alt=""></a>
                    </span>
                    <span>
                        <button class="add-btn">upload</button>
                    </span>
                </div>
            </div>
         </div>
        <!-- END HEADER -->

        <!-- BODY -->
         <div class="heroContainer">
            <div class="heroBg"></div>
            <div class="heroContent">
                <h1><a class="cool-text">Upload</a> CSV File</h1>
            </div>

            
            <!-- HOMEOWNER LIST -->
            <div class="homeowner-list-container">
                <h1 id="homeowner-list-header" class="hidden">List of <a class="cool-text">Homeowners</a></h1>
                <div id="homeowner-list" class="hidden"></div> 
            </div>
            <!-- END HOMEOWNER LIST -->

         </div>
        <!-- END BODY -->


        <!--- MODAL --->
        <div class="modal" id="uploadModal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <div class="modal-head">
                    <h2>Upload CSV File</h2>
                </div>
                <div class="modal-body">
                    <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <label class="custom-file-upload">
                            <input type="file" accept=".csv" name="csv_file" id="fileInput"/>
                            Choose File
                        </label>
                        <span id="fileName" style="display: none;"></span>
                    </form>
                </div>
            </div>
        </div>
        <!-- END MODAL -->

    </body>
</html>
