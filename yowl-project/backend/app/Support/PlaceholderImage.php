<?php

namespace App\Support;

/**
 * Generates the illustrations used by the demonstration seed.
 *
 * The first attempt pointed the seed at a public photograph service. The
 * addresses answered correctly one by one and failed once a browser asked for
 * twenty at once, which is rate limiting: a demonstration that depends on a
 * third party looks broken for reasons nobody can diagnose from the code.
 *
 * The images are drawn here instead, written to the configured media disk, and
 * served like any member upload. No network, no quota, and the seed exercises
 * the real storage path.
 */
class PlaceholderImage
{
    private const WIDTH = 900;

    private const HEIGHT = 600;

    /**
     * Palettes picked so that white text stays readable on every one of them.
     *
     * @var array<int, array{0: array{int, int, int}, 1: array{int, int, int}}>
     */
    private const PALETTES = [
        [[255, 107, 53], [255, 140, 90]],
        [[30, 42, 56], [52, 65, 85]],
        [[16, 84, 108], [37, 140, 160]],
        [[92, 46, 122], [140, 82, 178]],
        [[13, 92, 68], [32, 140, 100]],
        [[142, 45, 60], [190, 78, 88]],
        [[38, 60, 130], [70, 100, 180]],
        [[120, 78, 20], [180, 128, 44]],
    ];

    /**
     * Draw one illustration and return the path stored in the database.
     *
     * The same seed always produces the same image, so re-running the seeder
     * does not reshuffle the whole feed.
     */
    public static function make(string $seed, string $label): string
    {
        $path = 'seed/'.$seed.'.jpg';

        // Deja generee lors d'un seed precedent : on la reutilise.
        if (Media::disk()->exists($path)) {
            return $path;
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $hash = crc32($seed);
        [$from, $to] = self::PALETTES[$hash % count(self::PALETTES)];

        self::paintGradient($image, $from, $to);
        self::paintShapes($image, $hash);
        self::paintLabel($image, $label);

        ob_start();
        imagejpeg($image, null, 82);
        $binary = ob_get_clean();
        imagedestroy($image);

        Media::disk()->put($path, $binary);

        return $path;
    }

    /**
     * @param  array{int, int, int}  $from
     * @param  array{int, int, int}  $to
     */
    private static function paintGradient(\GdImage $image, array $from, array $to): void
    {
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $colour = imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) ($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) ($from[2] + ($to[2] - $from[2]) * $ratio),
            );
            imageline($image, 0, $y, self::WIDTH, $y, $colour);
        }
    }

    /**
     * A few translucent circles, so two images of the same palette differ.
     */
    private static function paintShapes(\GdImage $image, int $hash): void
    {
        $light = imagecolorallocatealpha($image, 255, 255, 255, 108);
        $dark = imagecolorallocatealpha($image, 0, 0, 0, 112);

        mt_srand($hash);
        for ($n = 0; $n < 5; $n++) {
            $diameter = mt_rand(140, 420);
            imagefilledellipse(
                $image,
                mt_rand(0, self::WIDTH),
                mt_rand(0, self::HEIGHT),
                $diameter,
                $diameter,
                $n % 2 === 0 ? $light : $dark
            );
        }
        mt_srand();
    }

    private static function paintLabel(\GdImage $image, string $label): void
    {
        $text = mb_strtoupper($label);
        $white = imagecolorallocate($image, 255, 255, 255);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 70);

        // Police interne : aucune dependance a un fichier de police sur le
        // systeme, qui varie d'une machine a l'autre.
        $font = 5;
        $charWidth = imagefontwidth($font);
        $x = (int) ((self::WIDTH - strlen($text) * $charWidth * 2) / 2);
        $y = (int) (self::HEIGHT / 2 - imagefontheight($font));

        // imagestring ne grossit pas : le texte est dessine sur une image
        // reduite puis agrandi, ce qui donne un titre lisible.
        $strip = imagecreatetruecolor(strlen($text) * $charWidth + 4, imagefontheight($font) + 4);
        imagesavealpha($strip, true);
        $transparent = imagecolorallocatealpha($strip, 0, 0, 0, 127);
        imagefill($strip, 0, 0, $transparent);
        imagestring($strip, $font, 2, 2, $text, $white);

        imagecopyresampled(
            $image,
            $strip,
            $x,
            $y,
            0,
            0,
            (int) (imagesx($strip) * 2),
            (int) (imagesy($strip) * 2),
            imagesx($strip),
            imagesy($strip)
        );
        imagedestroy($strip);

        unset($shadow);
    }
}
