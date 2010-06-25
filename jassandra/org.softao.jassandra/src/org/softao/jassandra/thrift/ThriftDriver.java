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
package org.softao.jassandra.thrift;

import java.net.URI;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.Properties;

import org.softao.jassandra.ConnectionUri;
import org.softao.jassandra.ConsistencyLevel;
import org.softao.jassandra.DriverManager;
import org.softao.jassandra.IConnection;
import org.softao.jassandra.IDriver;
import org.softao.jassandra.JassandraException;

/**
 * The implementation using the <code>Thrift</code> API.
 */
public class ThriftDriver implements IDriver {
	private static final int MAJOR_VER = 1;
	private static final int MINOR_VER = 0;

	private static final String THRIFT_SCHEMA = "thrift";
	private static final int DEFAULT_PORT = 9160;

	@Override
	public boolean acceptsURL(String url) throws JassandraException {
		if (url == null) {
			throw new NullPointerException("url");
		}
		URI uri = URI.create(url);
		String schema = uri.getScheme();

		if (schema != null && schema.equals(THRIFT_SCHEMA)) {
			return true;
		}

		return false;
	}

	@Override
	public IConnection connect(String url, Properties info)
			throws JassandraException {
		if (!acceptsURL(url)) {
			return null;
		}

		ConnectionUri uri = new ConnectionUri(url);
		String host = uri.getHost();
		if (host == null) {
			throw new JassandraException("host is missing.");
		}
		int port = uri.getPort();
		if (port < 0) {
			port = DEFAULT_PORT;
		}
		
		List<String> hostPortList = new ArrayList<String>();
		hostPortList.add(host + ":" + port);
		
		String alt = uri.getParameter("alt");
		if (alt != null) {
			hostPortList.addAll(Arrays.asList(alt.split(",")));
		}

		ThriftConnection connection = new ThriftConnection(hostPortList);

		String sCL = info.getProperty(DriverManager.CONSISTENCY_LEVEL);
		if (sCL != null) {
			ConsistencyLevel cl = Enum.valueOf(ConsistencyLevel.class, sCL);
			connection.setConsistencyLevel(cl);
		}

		return connection;
	}

	@Override
	public int getMajorVersion() {
		return MAJOR_VER;
	}

	@Override
	public int getMinorVersion() {
		return MINOR_VER;
	}
}
