<link rel="stylesheet" href="<?= asset('css/style.css') ?>">





<script>
    async function convertUrlToFile(url, filename) {
        // Buscar a imagem e convertê-la em Blob
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('Erro ao buscar a imagem');
        }

        const blob = await response.blob();

        // Criar um objeto File a partir do Blob
        return new File([blob], filename, {
            type: blob.type
        });
    }
</script>
