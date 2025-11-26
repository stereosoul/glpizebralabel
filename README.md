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

