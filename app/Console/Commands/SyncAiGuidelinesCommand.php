<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class SyncAiGuidelinesCommand extends Command
{
    /**
     * URL base da API do GitHub
     */
    private const string GITHUB_API_URL = 'https://api.github.com/repos/aronpc/filament-core-starter-kit/contents/.claude/docs';

    /**
     * Diretório local para salvar os documentos
     */
    private const string DOCS_DIR = '.claude/docs';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ai-guidelines {--force : Força o download mesmo que os arquivos já existam}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza documentos do repositório público filament-core-starter-kit';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('📚 Sincronizando documentos do repositório público...');

        try {
            // Criar diretório se não existir
            $this->createDocsDirectory();

            // Obter lista de arquivos do repositório
            $files = $this->getRepoFiles();

            if ($files === []) {
                $this->error('❌ Nenhum arquivo encontrado no repositório.');

                return;
            }

            $this->info('📋 '.count($files).' arquivos encontrados');

            // Baixar cada arquivo
            /** @var string[] $downloadedFiles */
            $downloadedFiles = [];
            foreach ($files as $file) {
                $result = $this->downloadFile($file);
                if ($result['success']) {
                    $downloadedFiles[] = $result['filename'];
                    $this->info("✅ {$file['name']}");
                } else {
                    $this->error("❌ Falha ao baixar {$file['name']}");
                }
            }

            // Atualizar CLAUDE.md
            if ($this->updateClaudeMd($downloadedFiles)) {
                $this->info('📝 CLAUDE.md atualizado com sucesso!');
            } else {
                $this->error('❌ Falha ao atualizar CLAUDE.md');

                return;
            }

            $this->info('🎉 Sincronização concluída com sucesso!');

            return;
        } catch (Exception $e) {
            $this->error('❌ Erro durante a sincronização: '.$e->getMessage());

            return;
        }
    }

    /**
     * Cria o diretório de documentos se não existir
     */
    private function createDocsDirectory(): void
    {
        if (! File::exists(self::DOCS_DIR)) {
            File::makeDirectory(self::DOCS_DIR, 0755, true);
            $this->info('📁 Diretório '.self::DOCS_DIR.' criado');
        }
    }

    /**
     * Obtém a lista de arquivos do repositório via GitHub API
     *
     * @return array<string, mixed>[]
     *
     * @throws ConnectionException|Throwable
     */
    private function getRepoFiles(): array
    {
        $this->info('🔍 Buscando arquivos no repositório...');

        $response = Http::get(self::GITHUB_API_URL);

        throw_unless($response->successful(), Exception::class, 'Falha ao acessar GitHub API: '.$response->status());

        /** @var array<string, mixed>[] $files */
        $files = $response->json();

        // Filtrar apenas arquivos .md e ordenar por nome
        return collect($files)
            ->filter(fn (array $file): bool => $file['type'] === 'file' && Str::endsWith($file['name'], '.md'))
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * Baixa um arquivo individual
     *
     * @param  array<string, mixed>  $file
     * @return array{success: bool, filename: string}
     *
     * @throws ConnectionException
     */
    private function downloadFile(array $file): array
    {
        $localPath = self::DOCS_DIR.'/'.$file['name'];

        // Verificar se arquivo já existe e não está em modo force
        if (File::exists($localPath) && ! $this->option('force')) {
            $this->line("⏭️  {$file['name']} (já existe, use --force para sobrescrever)");

            return ['success' => true, 'filename' => $file['name']];
        }

        // Baixar conteúdo do arquivo
        $response = Http::get($file['download_url']);

        if (! $response->successful()) {
            return ['success' => false, 'filename' => $file['name']];
        }

        // Salvar arquivo localmente
        File::put($localPath, $response->body());

        return ['success' => true, 'filename' => $file['name']];
    }

    /**
     * Atualiza o arquivo CLAUDE.md com a lista de documentos
     *
     * @param  string[]  $files
     *
     * @throws FileNotFoundException
     */
    private function updateClaudeMd(array $files): bool
    {
        $claudeMdPath = 'CLAUDE.md';

        if (! File::exists($claudeMdPath)) {
            $this->error('❌ Arquivo CLAUDE.md não encontrado');

            return false;
        }

        $content = File::get($claudeMdPath);

        // Criar nova lista de arquivos em ordem
        $fileReferences = collect($files)
            ->sort() // Ordena alfabeticamente os nomes dos arquivos
            ->map(fn ($file): string => "@.claude/docs/{$file}")
            ->implode("\n");

        // Encontrar e substituir o conteúdo entre as tags
        $pattern = '/<filament-core-startkit-guidelines>(.*?)<\/filament-core-startkit-guidelines>/s';
        $replacement = "<filament-core-startkit-guidelines>\n".$fileReferences."\n</filament-core-startkit-guidelines>";

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent === null) {
            $this->error('❌ Falha ao fazer substituição no CLAUDE.md');

            return false;
        }

        File::put($claudeMdPath, $newContent);

        return true;
    }
}
