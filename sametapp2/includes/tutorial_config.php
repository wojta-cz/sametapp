<?php
/**
 * Konfigurace tutorialu na přihlašovací stránce
 * Tento soubor můžete snadno upravit pro změnu obsahu tutorialu
 */

return [
    'title' => '🎯 Jak aplikace funguje',
    
    'steps' => [
        [
            'number' => '1',
            'title' => 'Vytvořte nebo připojte se k týmu',
            'description' => 'Po přihlášení si vytvořte vlastní tým nebo se připojte k existujícímu pomocí kódu týmu.'
        ],
        [
            'number' => '2',
            'title' => 'Procházejte stanoviště',
            'description' => 'Systém vám automaticky přiřadí stanoviště podle kapacity.'
        ],
        [
            'number' => '3',
            'title' => 'Skenujte QR kódy',
            'description' => 'U fyzických stanovišť organizátor naskenuje váš QR kód. U virtuálních stanovišť skenujete qr kódy rozmístěné po škole.'
        ],
        [
            'number' => '4',
            'title' => 'Sbírejte body a soutěžte',
            'description' => 'Za každé splněné stanoviště získáte body. Sledujte žebříček a bojujte o první místo!'
        ]
    ],
    
    'features' => [
        [
            'icon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
            'label' => 'Fyzická stanoviště'
        ],
        [
            'icon' => '<path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>',
            'label' => 'Virtuální úkoly'
        ],
        [
            'icon' => '<path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>',
            'label' => 'Týmová hra'
        ],
        [
            'icon' => '<path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>',
            'label' => 'Žebříček'
        ]
    ]
];