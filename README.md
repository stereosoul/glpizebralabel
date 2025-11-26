# GLPI Zebra Label Plugin

![GLPI Zebra Label](https://img.shields.io/badge/GLPI-Plugin-orange)
![License](https://img.shields.io/badge/License-GPL--3.0--or--later-blue)
![Version](https://img.shields.io/badge/Version-1.0.0-green)
![GLPI Version](https://img.shields.io/badge/GLPI-11.0.0+-success)

Plugin for generating ZPL labels for Zebra printers with QR codes and barcodes for GLPI assets.

## 🎯 Features

- 🖨️ **Generate ZPL code** for QR codes and barcodes
- 💾 **Support for multiple asset types**: Computers, Monitors, Network Equipment, Printers, Phones, Peripherals
- 🔗 **Easy integration** - "Print label" button in asset forms
- 📱 **QR codes with scan URLs** for quick inventory updates (physical inventory date update by scan)
- ⚡ **Fast generation** - instant ZPL code creation
- 🎯 **Optimized layout** - perfect for 70x30mm labels with 200 dpi



## 🚀 Installation

### Method 1: Manual Installation
1. Download the latest release from [Releases](https://github.com/stereosoul/glpizebralabel/releases)
2. Extract to `glpi/plugins/glpizebralabel/`
3. Activate the plugin in GLPI: **Setup > Plugins**

### Method 2: Git Clone
```bash
cd glpi/plugins/
git clone https://github.com/stereosoul/glpizebralabel.git
```

## 📖 Usage

    Navigate to any supported asset in GLPI

    Click "Print label" button in the asset form

    Choose between QR Code or Barcode label

    Download ZPL file and send to your Zebra printer
## Supported Asset Types

    💻 Computer

    🖥️ Monitor

    🌐 Network Equipment

    🖨️ Printer

    📞 Phone

    ⌨️ Peripheral

## 🤝 Contributing

Contributions are welcome!
    

## 📄 License

This project is licensed under the GPL-3.0-or-later License - see the LICENSE file for details.

## 🎯 О проекте

Этот GLPI-плагин создан для решения конкретной бизнес-задачи — автоматизации печати 
инвентарных этикеток и учета сканирований активов.

**Основной функционал:**
- 🖨️ Печать этикеток с QR-кодами и штрихкодами (Zebra printers)
- 📅 Автоматическое обновление даты инвентаризации при сканировании
- 🔄 Интеграция с жизненным циклом активов в GLPI

**Технические детали:**
Проект разработан с активным использованием AI-ассистентов для ускорения процесса 
разработки под конкретные требования.

🚨 **Используйте на свой страх и риск**. Буду рад, если плагин пригодится и другим!

## 👨‍💻 Author

vibecoded by Aleksei Meshkov

    GitHub: @stereosoul

    Repository: https://github.com/stereosoul/glpizebralabel


