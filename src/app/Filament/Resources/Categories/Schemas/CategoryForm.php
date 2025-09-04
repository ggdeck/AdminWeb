<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required(),

                Textarea::make('description')
                    ->label('Deskripsi'),

                FileUpload::make('image_url')
                    ->label('Gambar Kategori')
                    ->image()
                    ->directory('categories')
                    ->disk('public')
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        return time() . '.' . $file->getClientOriginalExtension();
                    })
                    ->dehydrated(true)
                    ->imagePreviewHeight('200') // Atur tinggi preview gambar
                    ->default(function ($record) {
                        // Mengatur URL default dari record saat mode edit
                        if ($record && $record->image_url && str_starts_with($record->image_url, 'http')) {
                            return $record->image_url;
                        }
                        return null;
                    })
                    ->afterStateUpdated(function ($state, callable $set, $get, $record) {
                        // Jika state adalah URL (saat edit), lewati proses upload
                        if (is_string($state) && str_starts_with($state, 'http')) {
                            return;
                        }

                        // Pastikan state adalah file yang diunggah
                        $file = is_array($state) ? $state[0] : $state;
                        if (!$file instanceof \Illuminate\Http\UploadedFile) {
                            return;
                        }

                        // Dapatkan path file sementara
                        $tempPath = $file->getRealPath();
                        if (!file_exists($tempPath)) {
                            throw new \Exception("❌ File tidak ditemukan: $tempPath");
                        }

                        // Nama file untuk Supabase
                        $fileName = time() . '-' . $file->getClientOriginalName();
                        $bucket = 'category-images';

                        // Hapus file lama jika ada (saat edit)
                        if ($record && $record->image_url) {
                            $oldFilePath = str_replace(
                                env('SUPABASE_URL') . "/storage/v1/object/public/{$bucket}/",
                                '',
                                $record->image_url
                            );
                            try {
                                Http::withHeaders([
                                    'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                                    'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                                ])->delete(
                                    env('SUPABASE_URL') . "/storage/v1/object/{$bucket}/{$oldFilePath}"
                                );
                                \Log::info("Deleted old file: {$oldFilePath}");
                            } catch (\Exception $e) {
                                \Log::error("Failed to delete old file: {$e->getMessage()}");
                            }
                        }

                        // Upload file baru
                        try {
                            $response = Http::withHeaders([
                                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                            ])->attach(
                                'file', file_get_contents($tempPath), $fileName
                            )->post(
                                env('SUPABASE_URL') . "/storage/v1/object/{$bucket}/categories/{$fileName}"
                            );

                            if ($response->successful()) {
                                $publicUrl = env('SUPABASE_URL') . "/storage/v1/object/public/{$bucket}/categories/{$fileName}";
                                \Log::info('Uploaded URL: ' . $publicUrl);

                                // Request untuk mendapatkan URL publik gambar yang diunggah
                                try {
                                    $publicUrlResponse = Http::withHeaders([
                                        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                                        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                                    ])->get(
                                        env('SUPABASE_URL') . "/storage/v1/object/public/{$bucket}/categories/{$fileName}"
                                    );

                                    if ($publicUrlResponse->successful()) {
                                        $set('image_url', $publicUrl);
                                        \Log::info('Successfully retrieved public URL: ' . $publicUrl);
                                    } else {
                                        throw new \Exception("❌ Gagal mengambil URL publik: " . $publicUrlResponse->body());
                                    }
                                } catch (\Exception $e) {
                                    \Log::error("❌ Error retrieving public URL: " . $e->getMessage());
                                    throw new \Exception("❌ Gagal mengambil URL publik gambar: " . $e->getMessage());
                                }
                            } else {
                                throw new \Exception("❌ Upload ke Supabase gagal: " . $response->body());
                            }
                        } catch (\Exception $e) {
                            \Log::error("❌ Error uploading to Supabase: " . $e->getMessage());
                            throw new \Exception("❌ Gagal mengunggah gambar: " . $e->getMessage());
                        }
                    })
                    ->required(),
            ]);
    }
}