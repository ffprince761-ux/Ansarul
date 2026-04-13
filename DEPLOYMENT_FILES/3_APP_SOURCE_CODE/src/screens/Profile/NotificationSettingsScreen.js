import React, { useState, useEffect, useContext } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert, Switch, TextInput, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { AppContext } from '../../context/AppContext';
import { scheduleAllNotifications, scheduleStoreReminders, cancelAllNotifications, sendTestNotification } from '../../services/NotificationService';

const NotificationSettingsScreen = ({ navigation }) => {
  const { bills, expenses } = useContext(AppContext);
  const [storeOpenTime, setStoreOpenTime] = useState({ hour: '09', minute: '00' });
  const [storeCloseTime, setStoreCloseTime] = useState({ hour: '21', minute: '00' });
  const [dueReminders, setDueReminders] = useState(true);
  const [dailyReport, setDailyReport] = useState(true);
  const [lowStockAlert, setLowStockAlert] = useState(true);
  const [storeReminder, setStoreReminder] = useState(true);
  const [showTimePicker, setShowTimePicker] = useState(null); // 'open' or 'close'
  const [tempHour, setTempHour] = useState('');
  const [tempMinute, setTempMinute] = useState('');

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      const settings = await AsyncStorage.getItem('notificationSettings');
      if (settings) {
        const parsed = JSON.parse(settings);
        if (parsed.storeOpenTime) setStoreOpenTime(parsed.storeOpenTime);
        if (parsed.storeCloseTime) setStoreCloseTime(parsed.storeCloseTime);
        if (parsed.dueReminders !== undefined) setDueReminders(parsed.dueReminders);
        if (parsed.dailyReport !== undefined) setDailyReport(parsed.dailyReport);
        if (parsed.lowStockAlert !== undefined) setLowStockAlert(parsed.lowStockAlert);
        if (parsed.storeReminder !== undefined) setStoreReminder(parsed.storeReminder);
      }
    } catch (e) {
      // silent
    }
  };

  const saveSettings = async () => {
    try {
      const settings = {
        storeOpenTime, storeCloseTime,
        dueReminders, dailyReport, lowStockAlert, storeReminder,
      };
      await AsyncStorage.setItem('notificationSettings', JSON.stringify(settings));

      // Re-schedule all notifications with new settings
      await cancelAllNotifications();
      await scheduleAllNotifications(bills, expenses);
      if (storeReminder) {
        await scheduleStoreReminders(storeOpenTime, storeCloseTime);
      }

      Alert.alert('Saved', 'Notification settings saved successfully!');
    } catch (e) {
      // silent
      Alert.alert('Error', 'Failed to save settings');
    }
  };

  const openTimePicker = (type) => {
    const time = type === 'open' ? storeOpenTime : storeCloseTime;
    setTempHour(time.hour);
    setTempMinute(time.minute);
    setShowTimePicker(type);
  };

  const confirmTime = () => {
    const h = parseInt(tempHour);
    const m = parseInt(tempMinute);
    if (isNaN(h) || isNaN(m) || h < 0 || h > 23 || m < 0 || m > 59) {
      Alert.alert('Error', 'Enter valid time (Hour: 0-23, Minute: 0-59)');
      return;
    }
    const time = { hour: String(h).padStart(2, '0'), minute: String(m).padStart(2, '0') };
    if (showTimePicker === 'open') setStoreOpenTime(time);
    else setStoreCloseTime(time);
    setShowTimePicker(null);
  };

  const formatTime = (t) => {
    const h = parseInt(t.hour);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
    return `${h12}:${t.minute} ${ampm}`;
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.header}>
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Notification Settings</Text>
          <TouchableOpacity onPress={saveSettings}>
            <Text style={{ color: '#FFF', fontWeight: 'bold', fontSize: 15 }}>Save</Text>
          </TouchableOpacity>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>

        {/* Store Timing */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Ionicons name="storefront" size={20} color="#2563EB" />
            <Text style={styles.sectionTitle}>Store Timing</Text>
          </View>
          <Text style={styles.sectionDesc}>App will remind you to open and close your store</Text>

          <View style={styles.toggleRow}>
            <Text style={styles.toggleLabel}>Store Reminder</Text>
            <Switch value={storeReminder} onValueChange={setStoreReminder} trackColor={{ true: '#2563EB' }} thumbColor={storeReminder ? '#FFF' : '#F4F3F4'} />
          </View>

          {storeReminder && (
            <View style={styles.timeRow}>
              <TouchableOpacity style={styles.timeBox} onPress={() => openTimePicker('open')}>
                <Ionicons name="sunny" size={22} color="#F59E0B" />
                <Text style={styles.timeLabel}>Open Time</Text>
                <Text style={styles.timeValue}>{formatTime(storeOpenTime)}</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.timeBox} onPress={() => openTimePicker('close')}>
                <Ionicons name="moon" size={22} color="#6366F1" />
                <Text style={styles.timeLabel}>Close Time</Text>
                <Text style={styles.timeValue}>{formatTime(storeCloseTime)}</Text>
              </TouchableOpacity>
            </View>
          )}
        </View>

        {/* Due Reminders */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Ionicons name="cash" size={20} color="#DC2626" />
            <Text style={styles.sectionTitle}>Due / Udhari Reminders</Text>
          </View>
          <Text style={styles.sectionDesc}>Get notified about due payments - kiska kitna baaki hai</Text>

          <View style={styles.toggleRow}>
            <Text style={styles.toggleLabel}>Due Date Reminders</Text>
            <Switch value={dueReminders} onValueChange={setDueReminders} trackColor={{ true: '#DC2626' }} thumbColor={dueReminders ? '#FFF' : '#F4F3F4'} />
          </View>
          {dueReminders && (
            <View style={styles.infoBox}>
              <View style={styles.infoRow}><Ionicons name="time" size={14} color="#F59E0B" /><Text style={styles.infoText}>8:00 AM - Due date pe yaad dilayega</Text></View>
              <View style={styles.infoRow}><Ionicons name="calendar" size={14} color="#8B5CF6" /><Text style={styles.infoText}>7:00 PM - Kal due hone wale bills</Text></View>
              <View style={styles.infoRow}><Ionicons name="warning" size={14} color="#DC2626" /><Text style={styles.infoText}>Overdue bills ka instant alert</Text></View>
              <View style={styles.infoRow}><Ionicons name="people" size={14} color="#2563EB" /><Text style={styles.infoText}>8:30 AM - Total pending collection</Text></View>
            </View>
          )}
        </View>

        {/* Daily Report */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Ionicons name="bar-chart" size={20} color="#10B981" />
            <Text style={styles.sectionTitle}>Daily Business Report</Text>
          </View>
          <Text style={styles.sectionDesc}>Roz raat ko aaj ka sales, expenses, profit summary</Text>

          <View style={styles.toggleRow}>
            <Text style={styles.toggleLabel}>Daily Report (9:00 PM)</Text>
            <Switch value={dailyReport} onValueChange={setDailyReport} trackColor={{ true: '#10B981' }} thumbColor={dailyReport ? '#FFF' : '#F4F3F4'} />
          </View>
        </View>

        {/* Low Stock */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Ionicons name="alert-circle" size={20} color="#F59E0B" />
            <Text style={styles.sectionTitle}>Low Stock Alert</Text>
          </View>
          <Text style={styles.sectionDesc}>Jab koi product ka stock kam ho jaye</Text>

          <View style={styles.toggleRow}>
            <Text style={styles.toggleLabel}>Low Stock Notifications</Text>
            <Switch value={lowStockAlert} onValueChange={setLowStockAlert} trackColor={{ true: '#F59E0B' }} thumbColor={lowStockAlert ? '#FFF' : '#F4F3F4'} />
          </View>
        </View>

        {/* Test & Actions */}
        <View style={styles.actionRow}>
          <TouchableOpacity style={[styles.actionBtn, { backgroundColor: '#2563EB' }]} onPress={async () => { await sendTestNotification(); Alert.alert('Done', 'Test notification sent!'); }}>
            <Ionicons name="notifications" size={18} color="#FFF" />
            <Text style={styles.actionBtnText}>Test Notification</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[styles.actionBtn, { backgroundColor: '#EF4444' }]} onPress={async () => { await cancelAllNotifications(); Alert.alert('Done', 'All notifications cleared'); }}>
            <Ionicons name="trash" size={18} color="#FFF" />
            <Text style={styles.actionBtnText}>Clear All</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.saveBtn} onPress={saveSettings}>
          <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.saveBtnGrad}>
            <Ionicons name="checkmark-circle" size={20} color="#FFF" />
            <Text style={styles.saveBtnText}>Save Settings</Text>
          </LinearGradient>
        </TouchableOpacity>

        <View style={{ height: 30 }} />
      </ScrollView>

      {/* Time Picker Modal */}
      <Modal visible={showTimePicker !== null} transparent animationType="fade" onRequestClose={() => setShowTimePicker(null)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalBox}>
            <Text style={styles.modalTitle}>Set {showTimePicker === 'open' ? 'Store Open' : 'Store Close'} Time</Text>
            <View style={styles.modalTimeRow}>
              <View style={styles.modalTimeInput}>
                <Text style={styles.modalTimeLabel}>Hour (0-23)</Text>
                <TextInput style={styles.modalInput} keyboardType="numeric" maxLength={2} value={tempHour} onChangeText={setTempHour} placeholder="HH" />
              </View>
              <Text style={styles.modalColon}>:</Text>
              <View style={styles.modalTimeInput}>
                <Text style={styles.modalTimeLabel}>Minute (0-59)</Text>
                <TextInput style={styles.modalInput} keyboardType="numeric" maxLength={2} value={tempMinute} onChangeText={setTempMinute} placeholder="MM" />
              </View>
            </View>
            <View style={styles.modalActions}>
              <TouchableOpacity onPress={() => setShowTimePicker(null)} style={styles.modalCancel}>
                <Text style={{ color: '#64748B', fontWeight: '600' }}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity onPress={confirmTime} style={styles.modalConfirm}>
                <Text style={{ color: '#FFF', fontWeight: 'bold' }}>Set Time</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { paddingTop: 20, paddingBottom: 16, paddingHorizontal: 20 },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFFFFF' },
  content: { flex: 1, padding: 16 },
  section: { backgroundColor: '#FFF', borderRadius: 14, padding: 16, marginBottom: 14, elevation: 2 },
  sectionHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#1E293B', marginLeft: 8 },
  sectionDesc: { fontSize: 12, color: '#94A3B8', marginBottom: 12 },
  toggleRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 8 },
  toggleLabel: { fontSize: 14, fontWeight: '600', color: '#1E293B' },
  timeRow: { flexDirection: 'row', marginTop: 10 },
  timeBox: { flex: 1, backgroundColor: '#F8FAFC', borderRadius: 12, padding: 14, marginHorizontal: 4, alignItems: 'center', borderWidth: 1, borderColor: '#E2E8F0' },
  timeLabel: { fontSize: 11, color: '#64748B', marginTop: 6 },
  timeValue: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginTop: 4 },
  infoBox: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 12, marginTop: 8 },
  infoRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 4 },
  infoText: { fontSize: 12, color: '#64748B', marginLeft: 8 },
  actionRow: { flexDirection: 'row', marginBottom: 14 },
  actionBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 12, borderRadius: 10, marginHorizontal: 4 },
  actionBtnText: { color: '#FFF', fontWeight: '600', fontSize: 13, marginLeft: 6 },
  saveBtn: { marginBottom: 10 },
  saveBtnGrad: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: 12 },
  saveBtnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16, marginLeft: 8 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center' },
  modalBox: { backgroundColor: '#FFF', borderRadius: 16, padding: 24, width: '85%' },
  modalTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginBottom: 20 },
  modalTimeRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginBottom: 20 },
  modalTimeInput: { alignItems: 'center' },
  modalTimeLabel: { fontSize: 11, color: '#64748B', marginBottom: 6 },
  modalInput: { borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 10, padding: 12, fontSize: 24, fontWeight: 'bold', textAlign: 'center', width: 80 },
  modalColon: { fontSize: 28, fontWeight: 'bold', color: '#1E293B', marginHorizontal: 12, marginTop: 16 },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end' },
  modalCancel: { paddingHorizontal: 20, paddingVertical: 10, marginRight: 10 },
  modalConfirm: { backgroundColor: '#2563EB', paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 },
});

export default NotificationSettingsScreen;
