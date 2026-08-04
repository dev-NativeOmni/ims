export default (config) => ({
    layout: config.layout || 'tahfidz',
    className: config.className || '',
    musyrifName: config.musyrifName || '',
    selectedDateFormatted: config.selectedDateFormatted || '',
    classUmmiJilid: config.classUmmiJilid || '',
    classUmmiHalaman: config.classUmmiHalaman || '',
    classUmmiHafalanSurah: config.classUmmiHafalanSurah || '',
    students: [],

    init() {
        this.students = (config.students || []).map(s => ({
            ...s,
            attendance: 'Belum Setor',
            customStatus: ''
        }));
    },

    get generatedText() {
        if (this.layout === 'ummi') {
            let text = `📝 *LAPORAN TAHFIZH ${this.className.toUpperCase()}*\n`;
            text += `👤 *Halaqah ${this.musyrifName}*\n`;
            text += `📅 *${this.selectedDateFormatted}*\n\n`;

            if (this.classUmmiJilid || this.classUmmiHalaman) {
                text += `✅ *Ummi Dewasa : ${this.classUmmiJilid} Halaman ${this.classUmmiHalaman}*\n`;
            } else {
                text += `✅ *Ummi Dewasa : -*\n`;
            }

            if (this.classUmmiHafalanSurah) {
                text += `✅ *Klasikal Hafalan Ummi : ${this.classUmmiHafalanSurah}*\n`;
            } else {
                text += `✅ *Klasikal Hafalan Ummi : -*\n`;
            }

            text += `\n✅ *Laporan Setoran Ziyadah*\n`;

            this.students.forEach((student, index) => {
                let statusStr = '';
                if (student.has_record) {
                    statusStr = student.progress;
                } else if (student.customStatus) {
                    statusStr = student.customStatus;
                } else {
                    statusStr = student.attendance;
                }
                text += `${index + 1}. ${student.name} : ${statusStr}\n`;
            });

            text += `\nNB : Pada Klasikal Hafalan Ummi, semua murid telah menyetorkan hafalan pada surat tersebut dengan Metode Ummi.\n\n`;
            text += `*Baarakallaahu lanaa bil qur'an* ✨`;
            return text;
        } else {
            let text = `📝 *Laporan Tahfidz ${this.className}*\n`;
            text += `👤 *Halaqoh ${this.musyrifName}*\n`;
            text += `📅 *${this.selectedDateFormatted}*\n\n`;

            this.students.forEach((student, index) => {
                let statusStr = '';
                if (student.has_record) {
                    // regular layout usually replaces // with space or similar, but let's keep it clean
                    statusStr = student.progress.replace(/ \(\d+(\.\d+)? Baris\)/g, ' ($&)').replace(/ Baris\)/g, ' baris)').replace(/ \(/g, ' (').replace(/\/\/ /g, '');
                } else if (student.customStatus) {
                    statusStr = student.customStatus;
                } else {
                    statusStr = student.attendance === 'Izin' ? 'ijin' : student.attendance.toLowerCase();
                }
                text += `${index + 1}. ${student.name} 👉 ${statusStr}\n`;
            });

            text += `\nSemoga Allah beri kemudahan dan kelancaran hafalannya sholih sholihah 🤲`;
            return text;
        }
    },

    copyToClipboard() {
        const text = this.generatedText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Laporan berhasil disalin ke clipboard!');
        }).catch(err => {
            console.error('Gagal menyalin:', err);
            alert('Gagal menyalin laporan, silakan salin teks di kotak preview secara manual.');
        });
    },

    shareToWhatsApp() {
        const text = encodeURIComponent(this.generatedText);
        window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
    }
});
