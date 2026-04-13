import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_URL as BASE_URL } from '../services/api';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        this.setState({
            error: error,
            errorInfo: errorInfo
        });
        this.logErrorToBackend(error, errorInfo);
    }

    logErrorToBackend = async (error, errorInfo) => {
        try {
            const userId = await AsyncStorage.getItem('userId');
            const deviceInfo = `Platform: ${Platform.OS}, Version: ${Platform.Version}`;

            const payload = {
                userId: userId ? parseInt(userId) : null,
                errorMessage: error.message,
                stackTrace: errorInfo?.componentStack || error.stack,
                deviceInfo: deviceInfo
            };

            // Use production API URL for error reporting
            const API_URL = `${BASE_URL}/report_error.php`;

            await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            // reported
        } catch (e) {
            // silent
        }
    };

    handleReload = () => {
        // Basic reload logic - might need Adjustment for Expo
        this.setState({ hasError: false });
        // If you want to force reload the entire app:
        // RNRestart.Restart(); // requires react-native-restart package
    };

    render() {
        if (this.state.hasError) {
            return (
                <View style={styles.container}>
                    <View style={styles.content}>
                        <Text style={styles.title}>Oops! Something went wrong.</Text>
                        <Text style={styles.subtitle}>The error has been reported to the dashboard.</Text>

                        <ScrollView style={styles.errorBox}>
                            <Text style={styles.errorText}>
                                {this.state.error && this.state.error.toString()}
                            </Text>
                        </ScrollView>

                        <TouchableOpacity style={styles.button} onPress={this.handleReload}>
                            <Text style={styles.buttonText}>Try Again</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            );
        }

        return this.props.children;
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f8f9fa',
        justifyContent: 'center',
        padding: 20,
    },
    content: {
        backgroundColor: 'white',
        borderRadius: 15,
        padding: 25,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 10,
        elevation: 5,
        alignItems: 'center',
    },
    title: {
        fontSize: 22,
        fontWeight: 'bold',
        color: '#dc3545',
        marginBottom: 10,
    },
    subtitle: {
        fontSize: 14,
        color: '#6c757d',
        marginBottom: 20,
        textAlign: 'center',
    },
    errorBox: {
        width: '100%',
        maxHeight: 200,
        backgroundColor: '#f1f3f5',
        borderRadius: 8,
        padding: 10,
        marginBottom: 20,
    },
    errorText: {
        fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
        fontSize: 12,
        color: '#495057',
    },
    button: {
        backgroundColor: '#2563EB',
        paddingVertical: 12,
        paddingHorizontal: 30,
        borderRadius: 25,
    },
    buttonText: {
        color: 'white',
        fontSize: 16,
        fontWeight: 'bold',
    },
});

export default ErrorBoundary;
