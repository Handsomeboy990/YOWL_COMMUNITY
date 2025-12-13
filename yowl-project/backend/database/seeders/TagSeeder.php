<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'uncategorized', 'love', 'instagood', 'photooftheday', 'fashion', 'beautiful', 'happy', 'cute', 'tbt',
            'follow', 'followme', 'picoftheday', 'art', 'photography', 'nature', 'travel', 'travelgram', 'wanderlust',
            'food', 'foodie', 'foodporn', 'yummy', 'delicious', 'recipe', 'foodstagram', 'coffee', 'tea', 'breakfast',
            'lunch', 'dinner', 'health', 'fitness', 'workout', 'gym', 'fit', 'wellness', 'motivation', 'inspiration',
            'life', 'lifestyle', 'selfcare', 'mentalhealth', 'mindfulness', 'goals', 'success', 'entrepreneur',
            'startup', 'business', 'smallbusiness', 'selling', 'shoplocal', 'shopping', 'style', 'ootd', 'streetstyle',
            'makeup', 'beauty', 'skincare', 'hair', 'nails', 'fashionblogger', 'beautyblogger', 'influencer',
            'creator', 'contentcreator', 'viral', 'trending', 'reels', 'shorts', 'tiktok', 'youtube', 'vlog', 'video',
            'music', 'song', 'album', 'concert', 'live', 'artist', 'photographer', 'film', 'cinema', 'design',
            'architecture', 'interiordesign', 'homedecor', 'diy', 'handmade', 'craft', 'illustration', 'drawing',
            'painting', 'poetry', 'quotes', 'quote', 'education', 'learning', 'study', 'student', 'teacher', 'technology',
            'gadgets', 'electronics', 'gaming', 'gamer', 'esports', 'memes', 'funny', 'humor', 'lol', 'comedy', 'pets',
            'dogs', 'cats', 'animals', 'naturephotography', 'landscape', 'sunset', 'beach', 'mountains', 'city', 'street',
            'family', 'friends', 'party', 'weekend', 'travelphotography', 'adventure', 'roadtrip', 'camping', 'fitnessmotivation',
            'runs', 'yoga', 'meditation', 'plantbased', 'vegan', 'vegetarian', 'foodblog', 'recipes', 'beautycare', 'skincareroutine'
        ];

        foreach ($names as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }
    }
}
