<?php
    /*
    MIT License

    Copyright (c) 2025 Daniel-Dog-dev

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
    */
    
    require(__DIR__ . "/directadmin-api.php");
    require(__DIR__ . "/../../config/config.directadmin.php");
    
    if(empty($argv) || empty($argv[1])){
        echo "No branch name given as first argument";
        exit(1);
    }

    $directadmin = new DirectAdminAPI($directadmin_login);

    $response2 = $directadmin->fetchGit($git_uuids);
    $response1 = $directadmin->setGitBranch($git_uuids, $argv[1]);
    $response3 = $directadmin->deployGit($git_uuids);

    echo "The GIT banch '" . $argv[1] . "' is set and deployed.\n";
?>