<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Support;

class ConsoleBanner
{
    public static function render(string $host, int $port, string $version = 'v1.0.0'): void
    {
        $serverAddress = "{$host}:{$port}";
        
        // ANSI Escape Codes for Styling
        $cyan = "\033[36m";
        $green = "\033[32m";
        $bold = "\033[1m";
        $reset = "\033[0m";
        $dim = "\033[2m";

        echo "{$cyan}{$bold}";
        echo " __     __   _           ____             _            \n";
        echo " \\ \\   / /__| | _____  _|  _ \\ ___  _   _| |_ ___ _ __ \n";
        echo "  \\ \\ / / _ \\ |/ _ \\ \/ / |_) / _ \\| | | | __/ _ \\ '__|\n";
        echo "   \\ V /  __/ | (_) >  <|  _ < (_) | |_| | ||  __/ |   \n";
        echo "    \_/ \___|_|\___/_/\_\_| \_\___/ \__,_|\__\\___|_|   \n";
        echo "                             {$version}                  \n";
        echo "{$reset}\n";

        echo " {$green}➜  {$bold}Local:{$reset}   http://{$serverAddress}\n";
        echo " {$dim}➜  Press {$bold}Ctrl+C{$reset}{$dim} to stop the server{$reset}\n\n";
    }
}
