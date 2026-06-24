# HafizPlus 2.0 — Mobile Storage Security Guideline

Dokumen ini memberikan panduan teknis bagi tim pengembang aplikasi mobile (mobile developers) HafizPlus 2.0 untuk menyimpan Personal Access Token (Laravel Sanctum) secara aman pada perangkat pengguna.

---

## 1. Ancaman Keamanan Token (Token Security Risks)

Token API HafizPlus memberikan akses penuh terhadap data santri, wali, guru, dan aktivitas hafalan. Jika token bocor, penyerang dapat memanipulasi data tanpa otorisasi. 

Beberapa ancaman utama meliputi:
*   **Reverse Engineering / Data Extraction**: Mengambil data token dari file sistem/database lokal yang tidak terenkripsi jika perangkat di-root/jailbreak.
*   **Leakage via Logs**: Token terekspos secara tidak sengaja di dalam log debug konsol (Logcat/Console).
*   **Backup Leak**: Token tersimpan di dalam backup cloud bawaan OS (Google Drive / iCloud Backup) dalam bentuk teks polos.

---

## 2. Aturan Emas Keamanan Token (Golden Rules)

1.  **DILARANG** menyimpan token di dalam penyimpanan lokal yang tidak terenkripsi, seperti:
    *   *Android*: SharedPreferences (bawaan tanpa enkripsi).
    *   *iOS*: UserDefaults (menyimpan data dalam file `.plist` teks polos).
    *   *Flutter*: `shared_preferences` package bawaan.
    *   *React Native*: `AsyncStorage` bawaan.
2.  **DILARANG** mencatat token di dalam logging konsol saat aplikasi berjalan di mode Production.
3.  **HARUS** membersihkan token dari penyimpanan lokal segera setelah pengguna menekan tombol **Logout**.

---

## 3. Panduan Implementasi Berdasarkan Platform

### 3.1 Flutter

Gunakan package **`flutter_secure_storage`** yang secara otomatis menggunakan **Keystore** di Android dan **Keychain** di iOS.

#### Instalasi:
```bash
flutter pub add flutter_secure_storage
```

#### Contoh Kode:
```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  final _storage = const FlutterSecureStorage();
  static const _tokenKey = 'hafizplus_api_token';

  // Menyimpan token secara aman
  Future<void> saveToken(String token) async {
    await _storage.write(
      key: _tokenKey, 
      value: token,
      aOptions: const AndroidOptions(encryptedSharedPreferences: true), // Wajib untuk Android
    );
  }

  // Membaca token
  Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  // Menghapus token saat logout
  Future<void> deleteToken() async {
    await _storage.delete(key: _tokenKey);
  }
}
```

---

### 3.2 React Native

Gunakan library **`react-native-keychain`** untuk perlindungan maksimal menggunakan hardware-backed storage.

#### Instalasi:
```bash
npm install react-native-keychain
# atau
yarn add react-native-keychain
```

#### Contoh Kode:
```javascript
import * as Keychain from 'react-native-keychain';

// Menyimpan token
async function saveToken(token) {
  try {
    await Keychain.setGenericPassword('hafizplus_user', token, {
      service: 'hafizplus_token_service',
      securityLevel: Keychain.SECURITY_LEVEL.SECURE_SOFTWARE, // Wajib proteksi hardware jika didukung
    });
  } catch (error) {
    console.error("Gagal menyimpan token secara aman", error);
  }
}

// Membaca token
async function getToken() {
  try {
    const credentials = await Keychain.getGenericPassword({
      service: 'hafizplus_token_service',
    });
    if (credentials) {
      return credentials.password; // Ini adalah token Anda
    }
    return null;
  } catch (error) {
    console.error("Gagal membaca token secara aman", error);
    return null;
  }
}

// Menghapus token (Logout)
async function deleteToken() {
  try {
    await Keychain.resetGenericPassword({
      service: 'hafizplus_token_service',
    });
  } catch (error) {
    console.error("Gagal menghapus token", error);
  }
}
```

---

### 3.3 Native Android (Kotlin / Java)

Gunakan **`EncryptedSharedPreferences`** yang merupakan bagian dari Android Jetpack Security.

#### Dependensi (build.gradle):
```groovy
dependencies {
    implementation "androidx.security:security-crypto:1.1.0-alpha06"
}
```

#### Contoh Kode (Kotlin):
```kotlin
import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKeys

object SecureTokenManager {
    private const val PREFS_FILE = "hafizplus_secure_prefs"
    private const val KEY_TOKEN = "hafizplus_api_token"

    private fun getSharedPrefs(context: Context) = EncryptedSharedPreferences.create(
        PREFS_FILE,
        MasterKeys.getOrCreate(MasterKeys.AES256_GCM_SPEC),
        context,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )

    fun saveToken(context: Context, token: String) {
        getSharedPrefs(context).edit().putString(KEY_TOKEN, token).apply()
    }

    fun getToken(context: Context): String? {
        return getSharedPrefs(context).getString(KEY_TOKEN, null)
    }

    fun deleteToken(context: Context) {
        getSharedPrefs(context).edit().remove(KEY_TOKEN).apply()
    }
}
```

---

### 3.4 Native iOS (Swift)

Gunakan iOS **`Keychain Services`** API langsung atau melalui wrapper library seperti `SwiftKeychainWrapper`.

#### Contoh Kode (Swift menggunakan Keychain Services):
```swift
import Foundation
import Security

class KeychainManager {
    static let shared = KeychainManager()
    private let service = "com.hafizplus.app"
    private let account = "api_token"

    func saveToken(token: String) -> Bool {
        guard let data = token.data(using: .utf8) else { return false }
        
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecValueData as String: data
        ]
        
        // Hapus token lama jika ada
        SecItemDelete(query as CFDictionary)
        
        let status = SecItemAdd(query as CFDictionary, nil)
        return status == errSecSuccess
    }

    func getToken() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: kCFBooleanTrue!,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        
        var dataTypeRef: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &dataTypeRef)
        
        if status == errSecSuccess, let data = dataTypeRef as? Data {
            return String(data: data, encoding: .utf8)
        }
        return nil
    }

    func deleteToken() -> Bool {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account
        ]
        
        let status = SecItemDelete(query as CFDictionary)
        return status == errSecSuccess
    }
}
```

---

## 4. Keamanan Selama Pengiriman data (Transport Security)

*   **HTTPS Only**: API di server Production hanya boleh diakses melalui protokol HTTPS (`https://`). Jangan pernah mengizinkan HTTP polos untuk komunikasi API.
*   **SSL Pinning (Opsional - Sangat Direkomendasikan)**: Untuk mencegah serangan *Man-In-The-Middle (MITM)* di wifi publik, lakukan SSL Pinning pada sertifikat SSL domain API HafizPlus Anda di dalam aplikasi mobile.
