<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
            function dir_depth_count($dir="global_assets") {
        
        $root = "C:";
        $spliter = "\\";
        $depth = "";

        if (__DIR__[0] == "/") {
            $spliter = "/";
        }

        $dir_parts = explode($spliter, __DIR__);

        if (empty($dir_parts[0])) {
            $root = "/{$dir_parts[1]}";
        }

                        $bound = $spliter=="/" ? 1 : 0;

                for ($i=sizeof($dir_parts)-1; $i > $bound; $i--) {
            $directory = $root; 
            for ($j = $spliter=="/" ? 2 : 1; $j <= $i; $j++) {$directory.="{$spliter}{$dir_parts[$j]}";}
            if (is_dir($directory)) {
                $folder = opendir($directory);
                while (($subfolder = readdir($folder)) !== FALSE && $folder) {
                    if (is_dir($directory.$spliter.$subfolder) && $subfolder==$dir) {return $depth;}
                }
            }

            $depth.="../";
        }

        return null;
    }
            $dir_depth = dir_depth_count();
            include_once "{$dir_depth}my_modules/system.php";
        ?>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?php echo $dir_depth ?>global_assets/fonts/font-awesome-pro-5/css/all.css">
        <link rel="stylesheet" href="<?php echo $dir_depth ?>global_assets/css/style.css">
        <title>index</title>
    </head>
    <body>
        <button id="hide_unhide_system">
            <i class="fad fa-chevron-circle-left"></i>
        </button>
        <br>
        
        <div class="system">
            <?php
                access_denied($_COOKIE, true);
                echo system_controls(file:__FILE__);
                echo file_folders(__DIR__);
            ?>
        </div>
        
        <script src="<?php echo $dir_depth ?>global_assets/js/jquery-3.6.0.min.js"></script>
        <script src="<?php echo $dir_depth ?>global_assets/js/script.js"></script>
    </body>
</html>