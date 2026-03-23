<?php 

namespace MediaPlatform\AA_Playtime;

use Gemini\Laravel\Facades\Gemini;

class GetListGeminiModels {

    public function getList()
    {
        $response = Gemini::models()->list();

        foreach ($response->models as $model) {
            echo "Model: " . $model->name . "  ";
            echo "Display Name: " . $model->displayName . "  (";
            echo "Description: " . $model->description . ")<br>";
            echo "-----------------------------------" . "<br>";
        }
    }
}