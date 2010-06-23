/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

import java.io.PrintWriter;
import java.util.Map;
import java.util.Properties;
import java.util.Map.Entry;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.atomic.AtomicBoolean;

import org.softao.jassandra.thrift.ThriftDriver;

/**
 * This is a factory class.
 */
public final class DriverManager {

	/**
	 * The Class DriverInfo.
	 */
	static class DriverInfo {

		/** The m driver. */
		IDriver mDriver;

		/** The m driver class. */
		Class<? extends IDriver> mDriverClass;

		/** The m driver class name. */
		String mDriverClassName;

		/**
		 * To string.
		 * 
		 * @return the string
		 */
		public String toString() {
			return ("driver[className=" + mDriverClassName + "," + mDriver + "]");
		}
	}

	/** Key for consistency level property of connection. */
	public static final String CONSISTENCY_LEVEL = "jassandra.consistencyLevel";

	/** The m initialized. */
	private static AtomicBoolean mInitialized = new AtomicBoolean(false);

	/** The Constant mDrives. */
	private static final Map<String, DriverInfo> mDrives = new ConcurrentHashMap<String, DriverInfo>();

	/** The m log writer. */
	private static PrintWriter mLogWriter = new PrintWriter(System.out);

	/** The Constant mLogSync. */
	private static final Object mLogSync = new Object();

	/**
	 * Register driver.
	 * 
	 * @param driver
	 *            the driver to be registered
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	public static void registerDriver(IDriver driver) throws JassandraException {
		if (!mInitialized.get()) {
			initialize();
		}

		DriverInfo di = new DriverInfo();
		di.mDriver = driver;
		di.mDriverClass = driver.getClass();
		di.mDriverClassName = di.mDriverClass.getName();
		mDrives.put(di.mDriverClassName, di);
	}

	/**
	 * Initialize.
	 * 
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	private static synchronized void initialize() throws JassandraException {
		if (mInitialized.get()) {
			return;
		}
		mInitialized.set(true);

		registerDriver(new ThriftDriver());
		println("Jassandra Driver Manager initialized.");
	}

	/**
	 * Gets the connection.
	 * <p>
	 * The following key is used for initializing the connections:
	 * <ul>
	 * <li>{@link #CONSISTENCY_LEVEL}: one of {@link ConsistencyLevel}, default
	 * {@link ConsistencyLevel#ONE} if not presented.
	 * </ul>
	 * 
	 * @param url
	 *            the url
	 * @param info
	 *            the info
	 * @return a connection to the cassandra.
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	public static synchronized IConnection getConnection(String url,
			Properties info) throws JassandraException {
		if (!mInitialized.get()) {
			initialize();
		}

		JassandraException reason = null;
		for (Entry<String, DriverInfo> entry : mDrives.entrySet()) {
			DriverInfo di = entry.getValue();
			if (di.mDriver.acceptsURL(url)) {
				try {
					IConnection result = di.mDriver.connect(url, info);
					if (result != null) {
						println("getConnection returning " + di);
						return result;
					}
				} catch (JassandraException e) {
					if (reason == null) {
						reason = e;
					}
				}
			}
		}
		if (reason != null) {
			println("getConnection failed: " + reason);
			throw reason;
		}

		println("getConnection: no suitable driver found for " + url);
		String errFmt = "No suitable driver found for %1$s. State: %2$s.";
		String errMsg = String.format(errFmt, url, "08001");
		throw new JassandraException(errMsg);
	}

	/**
	 * Println.
	 * 
	 * @param message
	 *            the message
	 */
	public static void println(String message) {
		synchronized (mLogSync) {
			if (mLogWriter != null) {
				mLogWriter.println(message);
				mLogWriter.flush();
			}
		}
	}

	/* Prevent the DriverManager class from being instantiated. */
	/**
	 * Instantiates a new driver manager.
	 */
	private DriverManager() {
	}
}
