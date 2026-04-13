import { Platform } from 'react-native';

// Lazy load expo-notifications to avoid console error on import in Expo Go
let N = null;
const getN = () => {
  if (!N) {
    try { N = require('expo-notifications'); } catch (e) { return null; }
  }
  return N;
};

const initHandler = () => {
  try {
    const mod = getN();
    if (!mod) return;
    mod.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowBanner: true,
        shouldShowList: true,
        shouldPlaySound: true,
        shouldSetBadge: true,
      }),
    });
  } catch (e) {}
};

export const requestNotificationPermissions = async () => {
  try {
    const mod = getN();
    if (!mod) return false;
    let finalStatus = 'granted';
    try {
      const { status } = await mod.getPermissionsAsync();
      finalStatus = status;
      if (status !== 'granted') {
        const { status: s } = await mod.requestPermissionsAsync();
        finalStatus = s;
      }
    } catch (e) { return true; }
    if (finalStatus !== 'granted') return false;
    if (Platform.OS === 'android') {
      try {
        await mod.setNotificationChannelAsync('default', {
          name: 'BINEST Notifications',
          importance: mod.AndroidImportance.HIGH,
          sound: 'default',
        });
      } catch (e) {}
    }
    return true;
  } catch (e) { return true; }
};

export const cancelAllNotifications = async () => {
  try { const mod = getN(); if (mod) await mod.cancelAllScheduledNotificationsAsync(); } catch (e) {}
};

const getSecondsUntil = (hour, minute) => {
  const now = new Date();
  const target = new Date();
  target.setHours(hour, minute, 0, 0);
  if (target <= now) target.setDate(target.getDate() + 1);
  return Math.max(Math.floor((target - now) / 1000), 60);
};

const getSecondsUntilDate = (dateStr, hour, minute) => {
  try {
    const target = new Date(dateStr);
    if (isNaN(target.getTime())) return -1;
    target.setHours(hour, minute, 0, 0);
    return Math.floor((target - new Date()) / 1000);
  } catch { return -1; }
};

const scheduleOne = async (content, seconds) => {
  try {
    const mod = getN();
    if (!mod || seconds < 60) return;
    await mod.scheduleNotificationAsync({
      content,
      trigger: { type: mod.SchedulableTriggerInputTypes.TIME_INTERVAL, seconds, repeats: false },
    });
  } catch (e) {}
};

export const scheduleDailyReport = async () => {
  await scheduleOne(
    { title: 'Daily Business Summary', body: 'Tap to check today\'s sales, expenses & profit report', data: { type: 'daily-report' }, sound: 'default' },
    getSecondsUntil(21, 0)
  );
};

export const scheduleDueReminders = async (bills) => {
  try {
    const dueBills = (bills || []).filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      return mode === 'due' && b.due_status !== 'paid' && b.due_date;
    });
    if (dueBills.length === 0) return;

    const today = new Date(); today.setHours(0,0,0,0);

    for (const bill of dueBills) {
      try {
        const dueDate = new Date(bill.due_date);
        if (isNaN(dueDate.getTime())) continue;
        dueDate.setHours(0,0,0,0);
        const name = bill.customer_name || bill.customerName || 'Customer';
        const rem = (parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0) - (parseFloat(bill.paid_amount) || 0);
        if (rem <= 0) continue;
        const rs = `Rs.${Math.round(rem)}`;

        // Due date morning 8 AM
        const s1 = getSecondsUntilDate(bill.due_date, 8, 0);
        if (s1 > 60) await scheduleOne({ title: 'Due Payment Today!', body: `${name} ka ${rs} aaj due hai! Invoice #${bill.invoice_number || bill.id}`, sound: 'default' }, s1);

        // Day before 7 PM
        const db = new Date(bill.due_date); db.setDate(db.getDate() - 1);
        const s2 = getSecondsUntilDate(db.toISOString().split('T')[0], 19, 0);
        if (s2 > 60) await scheduleOne({ title: 'Due Payment Tomorrow!', body: `${name} ka ${rs} kal due hai! Invoice #${bill.invoice_number || bill.id}`, sound: 'default' }, s2);
      } catch (e) {}
    }

    // Morning summary 8:30 AM
    const totalRem = dueBills.reduce((s, b) => s + ((parseFloat(b.grand_total || b.grandTotal || b.total) || 0) - (parseFloat(b.paid_amount) || 0)), 0);
    if (totalRem > 0) {
      await scheduleOne({ title: 'Due Collection Reminder', body: `Total ${dueBills.length} customers ka Rs.${Math.round(totalRem)} pending hai!`, sound: 'default' }, getSecondsUntil(8, 30));
    }
  } catch (e) {}
};

export const scheduleStoreReminders = async (openTime, closeTime) => {
  try {
    if (!openTime || !closeTime) return;
    const oH = parseInt(openTime.hour), oM = parseInt(openTime.minute);
    const cH = parseInt(closeTime.hour), cM = parseInt(closeTime.minute);

    let rH = oH, rM = oM - 15;
    if (rM < 0) { rM += 60; rH--; } if (rH < 0) rH = 23;
    await scheduleOne({ title: 'Store Open Karo!', body: `Dukaan ${oH > 12 ? oH-12 : oH}:${String(oM).padStart(2,'0')} ${oH >= 12 ? 'PM' : 'AM'} pe khulti hai!`, sound: 'default' }, getSecondsUntil(rH, rM));

    let crH = cH, crM = cM - 15;
    if (crM < 0) { crM += 60; crH--; } if (crH < 0) crH = 23;
    await scheduleOne({ title: 'Store Band Karne Ka Time!', body: `Dukaan ${cH > 12 ? cH-12 : cH}:${String(cM).padStart(2,'0')} ${cH >= 12 ? 'PM' : 'AM'} pe band hoti hai!`, sound: 'default' }, getSecondsUntil(crH, crM));
  } catch (e) {}
};

export const scheduleAllNotifications = async (bills, expenses) => {
  try {
    const mod = getN();
    if (!mod) return;

    initHandler();
    const ok = await requestNotificationPermissions();
    if (!ok) return;
    await cancelAllNotifications();

    let settings = {};
    try {
      const AS = require('@react-native-async-storage/async-storage').default;
      const s = await AS.getItem('notificationSettings');
      if (s) settings = JSON.parse(s);
    } catch (e) {}

    if (settings.dailyReport !== false) await scheduleDailyReport();
    if (settings.dueReminders !== false) await scheduleDueReminders(bills);
    if (settings.storeReminder !== false && settings.storeOpenTime && settings.storeCloseTime)
      await scheduleStoreReminders(settings.storeOpenTime, settings.storeCloseTime);
  } catch (e) {}
};

export const sendLocalPushNotification = async (title, body, data = {}) => {
  try {
    const mod = getN();
    if (!mod) return;
    initHandler();
    await mod.scheduleNotificationAsync({
      content: { title, body, sound: 'default', data },
      trigger: { type: mod.SchedulableTriggerInputTypes.TIME_INTERVAL, seconds: 1, repeats: false },
    });
  } catch (e) {}
};

export const sendTestNotification = async () => {
  try {
    const mod = getN();
    if (!mod) return;
    initHandler();
    await mod.scheduleNotificationAsync({
      content: { title: 'BINEST Notifications Active!', body: 'Notifications enabled. You will get daily reports & due reminders.', sound: 'default' },
      trigger: { type: mod.SchedulableTriggerInputTypes.TIME_INTERVAL, seconds: 2, repeats: false },
    });
  } catch (e) {}
};
