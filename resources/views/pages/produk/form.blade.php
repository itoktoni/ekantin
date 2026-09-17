<?php /** @var App\Models\Produk $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="produk_id_gerai" label="Gerai" :options="$gerai" />
                <x-input col="6" name="produk_nama" label="Nama Produk" />
                <x-select col="6" name="produk_kategori" label="Kategori" :options="$kategori" />
                <x-input col="6" type="number" name="produk_harga" label="Harga (Rp)" />
                <x-select col="6" name="produk_status" label="Status" :options="$status" />

                <x-file
                    name="produk_foto"
                    label="Foto Produk"
                    col="12"
                    accept="image/*"
                    :preview="true"
                    :value="$model?->produk_foto_url"
                    helper="Foto produk, maksimal 2 MB" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
